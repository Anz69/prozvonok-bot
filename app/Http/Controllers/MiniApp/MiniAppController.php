<?php

namespace App\Http\Controllers\MiniApp;

use App\Http\Controllers\Controller;
use App\Models\BotUser;
use App\Models\CheckJob;
use App\Models\Geo;
use App\Models\LoyaltyBonus;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Exceptions\InsufficientBalanceException;
use App\Services\BalanceService;
use App\Services\NumberFileParser;
use App\Services\PremiumService;
use App\Services\PricingService;
use App\Telegram\Support\AdminNotifier;
use App\Telegram\Support\Screens;
use App\Services\Telegram\WebAppAuth;
use App\Services\WorkingHours;
use App\Telegram\Support\Links;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MiniAppController extends Controller
{
    private function user(Request $request): BotUser
    {
        return $request->attributes->get('botUser');
    }

    /** POST /app/session — авторизация по Telegram initData (или local-байпас). */
    public function session(Request $request, WebAppAuth $auth)
    {
        if (app()->environment('local')) {
            $user = BotUser::firstOrCreate(
                ['telegram_id' => (int) env('TELEGRAM_DEV_ID', 999000000)],
                ['first_name' => 'Local', 'last_name' => 'Tester'],
            );
            $request->session()->put('mini_bot_user_id', $user->id);

            return response()->noContent();
        }

        $initData = (string) $request->header('X-Telegram-Init-Data', '');
        $data = $auth->validate($initData);
        $user = $data ? $auth->resolveUser($data) : null;

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->session()->put('mini_bot_user_id', $user->id);

        return response()->noContent();
    }

    public function dashboard(Request $request)
    {
        return Inertia::render('Dashboard', [
            'availability' => $this->availability($this->user($request)),
        ]);
    }

    public function check(Request $request)
    {
        return Inertia::render('CheckBase');
    }

    /** POST /app/check/quote — расчёт по загруженному файлу. */
    public function checkQuote(Request $request, NumberFileParser $parser, PricingService $pricing)
    {
        $request->validate([
            'geo' => 'required|string|size:2',
            'file' => 'required|file|max:25600', // 25 МБ
        ]);

        $user = $this->user($request);
        $geo = Geo::active()->where('code', strtoupper($request->string('geo')))->first();
        if (! $geo) {
            return response()->json(['message' => 'ГЕО недоступно'], 422);
        }

        $allowed = (array) Setting::get('allowed_formats', ['xlsx', 'txt']);
        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (! in_array($ext, $allowed, true)) {
            return response()->json(['message' => 'Формат не поддерживается (.xlsx или .txt)'], 422);
        }

        $relative = 'checks/' . $user->telegram_id . '_' . time() . '_' . Str::random(6) . '.' . $ext;
        Storage::disk('local')->putFileAs('checks', $request->file('file'), basename($relative));

        $parsed = $parser->parse(Storage::disk('local')->path($relative), $geo->code);
        $max = (int) Setting::get('max_numbers', 100000);

        if (count($parsed['valid']) === 0) {
            Storage::disk('local')->delete($relative);

            return response()->json(['message' => 'В файле не найдено корректных номеров'], 422);
        }
        if (count($parsed['valid']) > $max) {
            Storage::disk('local')->delete($relative);

            return response()->json(['message' => "Слишком много номеров. Максимум {$max}."], 422);
        }

        $quote = $pricing->quoteForUser($user, count($parsed['valid']), $geo);

        return response()->json([
            'token' => $relative,
            'geo' => $geo->code,
            'total' => $parsed['total'],
            'valid' => count($parsed['valid']),
            'free' => $quote['free'],
            'discount' => $quote['discount'],
            'cost' => $quote['cost'],
            'balance' => (float) $user->deposit_balance,
        ]);
    }

    /** POST /app/check/confirm — списание и постановка в обзвон. */
    public function checkConfirm(Request $request, PricingService $pricing, BalanceService $balance, WorkingHours $hours)
    {
        $request->validate(['token' => 'required|string', 'geo' => 'required|string|size:2']);
        $user = $this->user($request);
        $relative = $request->string('token')->toString();

        if (! str_starts_with($relative, 'checks/') || ! Storage::disk('local')->exists($relative)) {
            return response()->json(['message' => 'Файл не найден, загрузите заново'], 422);
        }

        $geo = Geo::active()->where('code', strtoupper($request->string('geo')))->first();
        $parsed = app(NumberFileParser::class)->parse(Storage::disk('local')->path($relative), $geo->code);
        $valid = count($parsed['valid']);
        $quote = $pricing->quoteForUser($user, $valid, $geo);

        if (! $balance->canAfford($user, $quote['cost'])) {
            return response()->json(['message' => 'Недостаточно средств. Пополните баланс.', 'need_topup' => true], 422);
        }

        $within = $hours->isWithin($user->timezone);
        $job = new CheckJob([
            'geo_code' => $geo->code,
            'numbers_total' => $parsed['total'],
            'numbers_valid' => $valid,
            'cost' => $quote['cost'],
            'discount_percent' => $quote['discount'],
            'free_applied' => $quote['free'],
            'input_path' => $relative,
            'status' => $within ? CheckJob::STATUS_QUEUED : CheckJob::STATUS_SCHEDULED,
            'queued_at' => now(),
            'scheduled_for' => $within ? null : $hours->nextStart($user->timezone),
        ]);
        $job->bot_user_id = $user->id;
        $job->save();

        if ($quote['cost'] > 0) {
            $balance->debit($user, $quote['cost'], Transaction::TYPE_CHARGE, Transaction::WALLET_DEPOSIT,
                "Проверка базы #{$job->id} ({$geo->code}, {$valid} номеров)", $job);
        }
        if ($quote['free'] > 0 && Setting::get('free_numbers_once', true)) {
            $user->update(['used_free_numbers' => true]);
        }
        $user->increment('files_checked');

        if ($within) {
            \App\Jobs\ProcessCheckJob::dispatch($job->id);
        }

        return response()->json([
            'ok' => true,
            'within' => $within,
            'valid' => $valid,
            'cost' => $quote['cost'],
            'eta' => $hours->etaLabel($user->timezone, $valid),
        ]);
    }

    public function topup(Request $request)
    {
        $bonuses = LoyaltyBonus::where('is_active', true)->orderBy('threshold')->get()
            ->map(fn ($b) => ['threshold' => (float) $b->threshold, 'bonus' => (float) $b->bonus]);

        return Inertia::render('Topup', ['bonuses' => $bonuses]);
    }

    /** POST /app/topup — счёт через менеджера + диплинк подтверждения. */
    public function topupStore(Request $request)
    {
        $user = $this->user($request);
        $min = (float) Setting::get('min_deposit', 10);
        $amount = round((float) $request->input('amount'), 5);

        if ($amount < $min) {
            return response()->json(['message' => "Минимальная сумма — {$min}\$"], 422);
        }

        $payment = Payment::create([
            'bot_user_id' => $user->id,
            'uid' => Str::random(24),
            'method' => Payment::METHOD_MANAGER,
            'network' => (string) Setting::get('usdt_network', 'TRC20'),
            'amount_expected' => $amount,
            'status' => Payment::STATUS_PENDING,
        ]);

        $payload = 'pay_' . $payment->uid;
        $amountStr = \App\Telegram\Support\Screens::money($amount);

        return response()->json([
            'amount' => $amountStr,
            'manager_url' => Links::managerPay($amountStr, $payload),
            'deep_link' => Links::deepLink($payload),
        ]);
    }

    public function calculator(Request $request)
    {
        return Inertia::render('Calculator');
    }

    public function profile(Request $request)
    {
        return Inertia::render('Profile');
    }

    public function referral(Request $request)
    {
        return Inertia::render('Referral');
    }

    public function faq(Request $request)
    {
        $items = collect((array) Setting::get('faq_items', []))
            ->map(fn ($i) => ['q' => (string) ($i['q'] ?? ''), 'a' => (string) ($i['a'] ?? '')])
            ->filter(fn ($i) => $i['q'] !== '' && $i['a'] !== '')
            ->values()
            ->all();

        return Inertia::render('Faq', ['items' => $items]);
    }

    public function premium(Request $request, PremiumService $premium)
    {
        return Inertia::render('Premium', [
            'tiers' => [
                ['key' => 'premium', 'name' => 'Премиум', 'price' => $premium->price('premium'), 'discount' => $premium->discount('premium')],
                ['key' => 'premium_plus', 'name' => 'Премиум+', 'price' => $premium->price('premium_plus'), 'discount' => $premium->discount('premium_plus')],
            ],
            'days' => (int) Setting::get('premium_days', 30),
            'trialDeposit' => (float) Setting::get('premium_trial_deposit', 1000),
            'paybackNumbers' => (int) Setting::get('premium_payback_numbers', 294000),
        ]);
    }

    /** POST /app/premium/activate — активация подписки списанием с депозита. */
    public function premiumActivate(Request $request, PremiumService $premium)
    {
        $request->validate(['tier' => 'required|in:premium,premium_plus']);
        $user = $this->user($request);

        try {
            $until = $premium->activate($user, $request->string('tier')->toString());
        } catch (InsufficientBalanceException) {
            return response()->json(['message' => 'Недостаточно средств на депозите. Пополните баланс.', 'need_topup' => true], 422);
        }

        return response()->json([
            'ok' => true,
            'tier' => $user->premium_tier,
            'until' => $until->format('d.m.Y'),
            'discount' => (int) $user->check_discount,
            'balance' => (float) $user->deposit_balance,
        ]);
    }

    /** POST /app/premium/trial — заявка на пробный премиум (как в боте). */
    public function premiumTrial(Request $request)
    {
        $user = $this->user($request);
        $threshold = (float) Setting::get('premium_trial_deposit', 1000);

        if ((float) $user->deposit_balance < $threshold) {
            return response()->json(['message' => 'Пробный премиум — при депозите от ' . Screens::money($threshold) . '$.'], 422);
        }

        $ticket = SupportTicket::create([
            'bot_user_id' => $user->id,
            'type' => SupportTicket::TYPE_TRIAL_PREMIUM,
            'message' => 'Запрос пробного премиума (mini app)',
        ]);
        app(AdminNotifier::class)->alert("Запрос пробного премиума #{$ticket->id} от id {$user->telegram_id}");

        return response()->json(['ok' => true, 'message' => 'Заявка на пробный премиум принята.']);
    }

    public function info(Request $request)
    {
        return Inertia::render('Info', [
            'links' => [
                'faq' => (string) Setting::get('faq_url', ''),
                'guide' => (string) Setting::get('guide_url', ''),
                'improve' => (string) Setting::get('improve_url', ''),
                'support' => (string) Setting::get('support_url', ''),
                'channel' => (string) Setting::get('channel_url', ''),
            ],
        ]);
    }

    /** Сколько номеров доступно к проверке по каждому ГЕО (для дашборда/баланса). */
    private function availability(BotUser $user): array
    {
        $pricing = app(PricingService::class);
        $available = (float) $user->deposit_balance + (float) $user->referral_balance;
        $discount = (int) $user->check_discount;

        return Geo::active()->orderBy('sort')->get()->map(fn (Geo $g) => [
            'code' => $g->code,
            'flag' => $g->flag,
            'numbers' => $pricing->affordableNumbers($available, $g, $discount),
        ])->all();
    }
}
