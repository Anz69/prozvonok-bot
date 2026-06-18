<?php

namespace App\Telegram\Conversations;

use App\Models\AdminAudit;
use App\Models\BotText;
use App\Models\Geo;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Services\BalanceService;
use App\Telegram\Support\Admin;
use App\Telegram\Support\Notifier;
use App\Telegram\Support\Screen;
use App\Telegram\Support\Screens;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Универсальный ввод одного значения для админки в боте: настройки, тарифы, тексты,
 * подтверждение/отклонение вывода, ответ на тикет. Один шаг ввода → применение.
 */
class AdminInputConversation extends BotConversation
{
    public string $kind = '';
    public string $key = '';

    public function start(Nutgram $bot, string $kind = '', string $key = '', string $prompt = ''): void
    {
        if (! Admin::is($bot->userId())) {
            $this->end();

            return;
        }

        $this->kind = $kind;
        $this->key = $key;
        $bot->sendMessage($prompt !== '' ? $prompt : 'Введите значение (или /start для отмены):');
        $this->next('apply');
    }

    public function apply(Nutgram $bot): void
    {
        if ($this->escaped($bot)) {
            return;
        }

        $value = trim((string) $bot->message()?->text);
        if ($value === '') {
            $bot->sendMessage('Пустое значение. Повторите ввод.');

            return;
        }

        [$ok, $message, $backCb] = match ($this->kind) {
            'setting' => $this->applySetting($value),
            'tariff' => $this->applyTariff($value),
            'text' => $this->applyText($value),
            'wd_approve' => $this->approveWithdrawal($value),
            'wd_reject' => $this->rejectWithdrawal($value),
            'tk_reply' => $this->replyTicket($value),
            default => [false, 'Неизвестное действие.', 'adm:home'],
        };

        \Illuminate\Support\Facades\Cache::flush();
        $bot->sendMessage(($ok ? '✅ ' : '⚠️ ') . $message, reply_markup: InlineKeyboardMarkup::make()
            ->addRow(InlineKeyboardButton::make('⬅️ В админку', callback_data: $backCb)));
        $this->end();
    }

    private function applySetting(string $value): array
    {
        $setting = Setting::where('key', $this->key)->first();
        if (! $setting) {
            return [false, 'Настройка не найдена.', 'adm:settings'];
        }
        Setting::put($this->key, $value, $setting->type, $setting->group);
        AdminAudit::log('bot_setting_edit', $setting, ['key' => $this->key, 'value' => $value]);

        return [true, "Настройка «{$this->key}» = {$value}", 'adm:settings'];
    }

    private function applyTariff(string $value): array
    {
        $geo = Geo::where('code', $this->key)->first();
        if (! $geo) {
            return [false, 'ГЕО не найдено.', 'adm:tariffs'];
        }
        $price = (float) str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $value));
        $geo->update(['price_per_1000' => $price]);
        AdminAudit::log('bot_tariff_edit', $geo, ['price' => $price]);

        return [true, "Тариф {$geo->code} = {$price}\$ / 1000", 'adm:tariffs'];
    }

    private function applyText(string $value): array
    {
        BotText::updateOrCreate(['key' => $this->key], ['content' => $value]);
        AdminAudit::log('bot_text_edit', null, ['key' => $this->key]);

        return [true, "Текст «{$this->key}» обновлён.", 'adm:texts'];
    }

    private function approveWithdrawal(string $txHash): array
    {
        $w = Withdrawal::where('id', (int) $this->key)->where('status', Withdrawal::STATUS_PENDING)->first();
        if (! $w) {
            return [false, 'Заявка не найдена или обработана.', 'adm:withdrawals'];
        }
        $w->update(['status' => Withdrawal::STATUS_PAID, 'tx_hash' => $txHash, 'processed_at' => now()]);
        AdminAudit::log('bot_withdrawal_approve', $w, ['tx_hash' => $txHash]);
        app(Notifier::class)->notify($w->botUser, "✅ Вывод " . Screens::money($w->amount) . "\$ выполнен. Хэш: {$txHash}");

        return [true, "Вывод #{$w->id} одобрен.", 'adm:withdrawals'];
    }

    private function rejectWithdrawal(string $reason): array
    {
        $w = Withdrawal::where('id', (int) $this->key)->where('status', Withdrawal::STATUS_PENDING)->first();
        if (! $w) {
            return [false, 'Заявка не найдена или обработана.', 'adm:withdrawals'];
        }
        app(BalanceService::class)->credit($w->botUser, (float) $w->amount, Transaction::TYPE_REFUND, Transaction::WALLET_REFERRAL, "Возврат по заявке #{$w->id}", $w);
        $w->update(['status' => Withdrawal::STATUS_REJECTED, 'reason' => $reason, 'processed_at' => now()]);
        AdminAudit::log('bot_withdrawal_reject', $w, ['reason' => $reason]);
        app(Notifier::class)->notify($w->botUser, "❌ Заявка на вывод отклонена: {$reason}. Средства возвращены на реф. баланс.");

        return [true, "Вывод #{$w->id} отклонён, средства возвращены.", 'adm:withdrawals'];
    }

    private function replyTicket(string $text): array
    {
        $t = SupportTicket::find((int) $this->key);
        if (! $t) {
            return [false, 'Тикет не найден.', 'adm:tickets'];
        }
        app(Notifier::class)->notify($t->botUser, "📩 Ответ поддержки:\n\n{$text}");
        $t->update(['status' => 'closed']);
        AdminAudit::log('bot_ticket_reply', $t);

        return [true, "Ответ отправлен, тикет #{$t->id} закрыт.", 'adm:tickets'];
    }
}
