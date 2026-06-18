<?php

namespace App\Telegram\Handlers;

use App\Models\BotUser;
use App\Models\SupportTicket;
use App\Services\PremiumService;
use App\Telegram\Conversations\CalculatorConversation;
use App\Telegram\Conversations\CheckBaseConversation;
use App\Telegram\Conversations\TopupConversation;
use App\Telegram\Conversations\WithdrawConversation;
use App\Telegram\Support\AdminNotifier;
use App\Telegram\Support\Screen;
use App\Telegram\Support\Screens;
use SergiX44\Nutgram\Nutgram;

/**
 * Навигация в режиме одного экрана: nav:{key} перерисовывает текущий экран,
 * act:{action} запускает действие/диалог.
 */
class Navigator
{
    private function user(Nutgram $bot): BotUser
    {
        return $bot->get('bot_user');
    }

    /** Статические экраны (редактируем одно сообщение). Работает и по callback, и по тексту. */
    public function nav(Nutgram $bot, string $key): void
    {
        if ($bot->callbackQuery() !== null) {
            $bot->answerCallbackQuery();
        }
        $user = $this->user($bot);
        $screen = Screens::byKey($user, $key);

        Screen::show($bot, $screen['text'], $screen['keyboard'], $key);
    }

    /** Нижняя кнопка «⬅️ Назад»: возврат на предыдущий экран по карте. */
    public function back(Nutgram $bot): void
    {
        $current = (string) (($this->user($bot)->state['screen'] ?? 'home'));
        $this->nav($bot, Screen::BACK[$current] ?? 'home');
    }

    /** Действия: диалоги, премиум, заявки. */
    public function act(Nutgram $bot, string $action): void
    {
        // Премиум-активация: act:prem:premium | act:prem:premium_plus
        if (str_starts_with($action, 'prem:')) {
            $this->activatePremium($bot, substr($action, 5));

            return;
        }

        match ($action) {
            'check_base' => $this->startConversation($bot, CheckBaseConversation::class),
            'calculator' => $this->startConversation($bot, CalculatorConversation::class),
            'topup' => $this->startConversation($bot, TopupConversation::class),
            'withdraw' => $this->startConversation($bot, WithdrawConversation::class),
            'support' => $this->startSupport($bot),
            'percent' => $this->percentRequest($bot),
            'prem_trial' => $this->premiumTrial($bot),
            default => $bot->answerCallbackQuery(),
        };
    }

    private function startConversation(Nutgram $bot, string $conversation): void
    {
        $bot->answerCallbackQuery();
        $conversation::begin($bot);
    }

    private function startSupport(Nutgram $bot): void
    {
        $bot->answerCallbackQuery();
        \App\Telegram\Conversations\SupportConversation::begin($bot);
    }

    private function activatePremium(Nutgram $bot, string $tier): void
    {
        $user = $this->user($bot);
        $premium = app(PremiumService::class);
        $tier = $tier === PremiumService::TIER_PREMIUM_PLUS ? PremiumService::TIER_PREMIUM_PLUS : PremiumService::TIER_PREMIUM;

        if (! app(\App\Services\BalanceService::class)->canAfford($user, $premium->price($tier))) {
            $bot->answerCallbackQuery(
                text: \App\Models\BotText::render('err_premium_deposit', ['price' => (int) $premium->price($tier)]),
                show_alert: true,
            );

            return;
        }

        $premium->activate($user, $tier);
        $bot->answerCallbackQuery(text: '💎 Премиум активирован', show_alert: true);

        $screen = Screens::premium($user->fresh());
        Screen::show($bot, $screen['text'], $screen['keyboard']);
    }

    private function premiumTrial(Nutgram $bot): void
    {
        $user = $this->user($bot);
        $threshold = (float) \App\Models\Setting::get('premium_trial_deposit', 1000);

        if ((float) $user->deposit_balance < $threshold) {
            $bot->answerCallbackQuery(text: "Пробный премиум — при депозите от {$threshold}\$.", show_alert: true);

            return;
        }

        $ticket = SupportTicket::create([
            'bot_user_id' => $user->id,
            'type' => SupportTicket::TYPE_TRIAL_PREMIUM,
            'message' => 'Запрос пробного премиума',
        ]);
        app(AdminNotifier::class)->alert("Запрос пробного премиума #{$ticket->id} от id {$user->telegram_id}");

        $bot->answerCallbackQuery(text: 'Заявка на пробный премиум принята.', show_alert: true);
    }

    private function percentRequest(Nutgram $bot): void
    {
        $user = $this->user($bot);
        $ticket = SupportTicket::create([
            'bot_user_id' => $user->id,
            'type' => SupportTicket::TYPE_PERCENT_REQUEST,
            'message' => 'Запрос повышения реф. процента',
        ]);
        app(AdminNotifier::class)->alert("Запрос повышения % #{$ticket->id} от id {$user->telegram_id}");

        $bot->answerCallbackQuery(text: 'Заявка отправлена менеджеру', show_alert: true);
    }
}
