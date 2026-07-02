<?php

namespace App\Telegram\Conversations;

use App\Models\BotText;
use App\Models\BotUser;
use App\Models\CheckJob;
use App\Models\Geo;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\BalanceService;
use App\Services\NumberFileParser;
use App\Services\PricingService;
use App\Services\WorkingHours;
use App\Telegram\Support\Screen;
use App\Telegram\Support\Screens;
use Illuminate\Support\Facades\Storage;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Проверка базы (раздел 3.3) в режиме одного экрана:
 * ГЕО → файл → стоимость → списание → очередь.
 */
class CheckBaseConversation extends BotConversation
{
    public ?string $geoCode = null;
    public ?string $inputPath = null;
    public int $valid = 0;
    public int $total = 0;
    public int $free = 0;
    public int $discount = 0;
    public float $cost = 0;

    private function homeKb(): InlineKeyboardMarkup
    {
        return InlineKeyboardMarkup::make()->addRow(...Screen::navRow('home'));
    }

    public function start(Nutgram $bot): void
    {
        $kb = GeoKeyboard::make('check:geo:');
        $kb->addRow(...Screen::navRow('home'));
        $this->screen($bot, BotText::render('check_base_geo_select'), $kb);
        $this->next('chooseGeo');
    }

    public function chooseGeo(Nutgram $bot): void
    {
        if ($this->escaped($bot)) {
            return;
        }

        $code = GeoKeyboard::extract($bot, 'check:geo:');
        if ($code === null) {
            $bot->answerCallbackQuery();

            return;
        }

        $this->geoCode = $code;
        $geo = Geo::where('code', $code)->first();
        $bot->answerCallbackQuery();
        $this->screen($bot, BotText::render('check_base_info', [
            'formats' => implode(', ', (array) Setting::get('allowed_formats', ['xlsx', 'txt'])),
            'price' => Screens::money($geo->price_per_1000),
            'geo' => $geo->flag . ' ' . $geo->code,
            'avg_minutes' => Setting::get('avg_processing_minutes', 15),
            'wh_start' => Setting::get('working_hours_start', 9),
            'wh_end' => Setting::get('working_hours_end', 21),
        ]), $this->homeKb());
        $this->next('handleFile');
    }

    public function handleFile(Nutgram $bot): void
    {
        if ($this->escaped($bot)) {
            return;
        }

        $document = $bot->message()?->document;
        if ($document === null) {
            $this->screen($bot, '📎 Пришлите файл с номерами (.xlsx или .txt).', $this->homeKb());

            return;
        }

        $allowed = (array) Setting::get('allowed_formats', ['xlsx', 'txt']);
        $extension = strtolower(pathinfo((string) $document->file_name, PATHINFO_EXTENSION));
        if (! in_array($extension, $allowed, true)) {
            $this->screen($bot, BotText::render('err_format'), $this->homeKb());

            return;
        }

        Storage::disk('local')->makeDirectory('checks');
        $relative = "checks/{$bot->userId()}_" . time() . ".{$extension}";
        $absolute = Storage::disk('local')->path($relative);

        $file = $bot->getFile($document->file_id);
        if ($file === null || ! $bot->downloadFile($file, $absolute)) {
            $this->screen($bot, BotText::render('err_general', ['support_url' => Setting::get('support_url')]), $this->homeKb());
            $this->end();

            return;
        }

        $parsed = app(NumberFileParser::class)->parse($absolute, (string) $this->geoCode);
        $max = (int) Setting::get('max_numbers', 100000);

        if ($parsed['total'] === 0 || count($parsed['valid']) === 0) {
            $this->screen($bot, BotText::render('err_empty_file'), $this->homeKb());
            Storage::disk('local')->delete($relative);

            return;
        }
        if (count($parsed['valid']) > $max) {
            $this->screen($bot, BotText::render('err_limit', ['max' => $max]), $this->homeKb());
            Storage::disk('local')->delete($relative);

            return;
        }

        /** @var BotUser $user */
        $user = $bot->get('bot_user');
        $geo = Geo::where('code', $this->geoCode)->first();
        $quote = app(PricingService::class)->quoteForUser($user, count($parsed['valid']), $geo);

        $this->inputPath = $relative;
        $this->valid = count($parsed['valid']);
        $this->total = $parsed['total'];
        $this->free = $quote['free'];
        $this->discount = $quote['discount'];
        $this->cost = $quote['cost'];

        if ((float) $user->deposit_balance < $quote['cost']) {
            $kb = InlineKeyboardMarkup::make()
                ->addRow(InlineKeyboardButton::make('💰 Пополнить', callback_data: 'act:topup'))
                ->addRow(...Screen::navRow('home'));
            $this->screen($bot, BotText::render('err_insufficient', [
                'amount' => Screens::money($quote['cost']),
                'balance' => Screens::money($user->deposit_balance),
            ]), $kb);
            $this->end();

            return;
        }

        $kb = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('✅ Подтвердить', callback_data: 'check:confirm'),
                InlineKeyboardButton::make('❌ Отмена', callback_data: 'nav:home'),
            );

        $this->screen($bot, BotText::render('check_base_confirm', [
            'geo' => $geo->flag . ' ' . $geo->code,
            'valid' => $this->valid,
            'total' => $this->total,
            'free' => $this->free,
            'discount' => $this->discount,
            'cost' => Screens::money($this->cost),
            'balance' => Screens::money($user->deposit_balance),
        ]), $kb);
        $this->next('confirm');
    }

    public function confirm(Nutgram $bot): void
    {
        if ($this->escaped($bot)) { // ❌ Отмена = nav:home — обработается здесь
            if ($this->inputPath) {
                Storage::disk('local')->delete($this->inputPath);
            }

            return;
        }

        if (($bot->callbackQuery()?->data) !== 'check:confirm') {
            return;
        }

        /** @var BotUser $user */
        $user = $bot->get('bot_user');
        $geo = Geo::where('code', $this->geoCode)->first();
        $balance = app(BalanceService::class);

        if (! $balance->canAfford($user, $this->cost)) {
            $bot->answerCallbackQuery();
            $this->screen($bot, BotText::render('err_insufficient', [
                'amount' => Screens::money($this->cost),
                'balance' => Screens::money($user->deposit_balance),
            ]), $this->homeKb());
            $this->end();

            return;
        }

        $hours = app(WorkingHours::class);
        $within = $hours->isWithin($user->timezone);

        $job = new CheckJob([
            'geo_code' => $this->geoCode,
            'numbers_total' => $this->total,
            'numbers_valid' => $this->valid,
            'cost' => $this->cost,
            'discount_percent' => $this->discount,
            'free_applied' => $this->free,
            'input_path' => $this->inputPath,
            'status' => $within ? CheckJob::STATUS_QUEUED : CheckJob::STATUS_SCHEDULED,
            'queued_at' => now(),
            'scheduled_for' => $within ? null : $hours->nextStart($user->timezone),
        ]);
        $job->bot_user_id = $user->id;
        $job->save();

        if ($this->cost > 0) {
            $balance->debit(
                $user,
                $this->cost,
                Transaction::TYPE_CHARGE,
                Transaction::WALLET_DEPOSIT,
                "Проверка базы #{$job->id} ({$geo->code}, {$this->valid} номеров)",
                $job,
            );
        }

        if ($this->free > 0 && Setting::get('free_numbers_once', true)) {
            $user->update(['used_free_numbers' => true]);
        }
        $user->increment('files_checked');

        if ($within) {
            \App\Jobs\ProcessCheckJob::dispatch($job->id);
        }

        $bot->answerCallbackQuery(text: '✅ Принято');

        $text = BotText::render('check_base_queued', [
            'valid' => $this->valid,
            'geo' => $geo->flag . ' ' . $geo->code,
            'cost' => Screens::money($this->cost),
        ]);
        if (! $within) {
            $text .= "\n\n" . BotText::render('err_off_hours', [
                'wh_start' => $hours->start(),
                'wh_end' => $hours->end(),
            ]);
        }
        $text .= "\n\n⏱ Готовность файла — {$hours->etaLabel($user->timezone, $this->valid)}.";
        $this->screen($bot, $text, $this->homeKb());
        $this->end();
    }
}
