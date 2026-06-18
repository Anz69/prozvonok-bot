<?php

namespace App\Telegram\Handlers;

use App\Models\Payment;
use App\Services\Usdt\PaymentService;
use App\Telegram\Support\Admin;
use App\Telegram\Support\Screen;
use App\Telegram\Support\Screens;
use SergiX44\Nutgram\Nutgram;

/**
 * Подтверждение/отмена оплаты через менеджера (по кнопкам после открытия pay-диплинка).
 * Доступно только админам.
 */
class PaymentHandler
{
    private function adminPayment(Nutgram $bot, string $uid): ?Payment
    {
        if (! Admin::is($bot->userId())) {
            $bot->answerCallbackQuery(text: 'Недостаточно прав', show_alert: true);

            return null;
        }

        $payment = Payment::where('uid', $uid)->where('method', Payment::METHOD_MANAGER)->first();
        if ($payment === null || $payment->status !== Payment::STATUS_PENDING) {
            $bot->answerCallbackQuery(text: 'Платёж не найден или уже обработан', show_alert: true);

            return null;
        }

        return $payment;
    }

    /** Менеджер подтверждает оплату → зачисление пользователю. */
    public function confirmManager(Nutgram $bot, string $uid): void
    {
        $payment = $this->adminPayment($bot, $uid);
        if ($payment === null) {
            return;
        }

        app(PaymentService::class)->confirmManual($payment); // зачислит + уведомит пользователя
        $target = $payment->botUser;

        $bot->answerCallbackQuery(text: 'Зачислено', show_alert: true);
        Screen::show(
            $bot,
            "✅ Оплата подтверждена.\nЗачислено " . Screens::money($payment->amount_expected)
            . "\$ пользователю {$target->displayName()} (id {$target->telegram_id}).",
        );
    }

    /** Менеджер отменяет счёт. */
    public function cancelManager(Nutgram $bot, string $uid): void
    {
        $payment = $this->adminPayment($bot, $uid);
        if ($payment === null) {
            return;
        }

        $payment->update(['status' => Payment::STATUS_CANCELLED]);
        $bot->answerCallbackQuery(text: 'Отменено');
        Screen::show($bot, '❌ Платёж отменён. Средства не зачислены.');
    }
}
