<?php

namespace App\Console\Commands;

use App\Models\BotUser;
use App\Services\PremiumService;
use App\Telegram\Support\Notifier;
use Illuminate\Console\Command;

/**
 * Премиум (раздел 3.4b): уведомление за 1 день до конца и деактивация по истечении.
 * В расписании — ежедневно.
 */
class ExpirePremium extends Command
{
    protected $signature = 'premium:expire';

    protected $description = 'Уведомить об окончании и деактивировать истёкшие премиум-подписки';

    public function handle(PremiumService $premium, Notifier $notifier): int
    {
        // Уведомление за 1 день
        $soon = BotUser::whereNotNull('premium_tier')
            ->whereBetween('premium_until', [now(), now()->addDay()])
            ->get();
        foreach ($soon as $user) {
            $notifier->notify($user, '🔔 Ваша премиум-подписка заканчивается завтра ('
                . $user->premium_until->format('d.m.Y') . '). Продлите, чтобы сохранить скидку.');
        }

        // Деактивация истёкших
        $expired = BotUser::whereNotNull('premium_tier')
            ->where('premium_until', '<', now())
            ->get();
        foreach ($expired as $user) {
            // авто-продление (опционально) — если включено и хватает депозита
            if ($user->premium_auto_renew
                && app(\App\Services\BalanceService::class)->canAfford($user, $premium->price($user->premium_tier))) {
                $premium->activate($user, $user->premium_tier);
                $notifier->notify($user, '🔄 Премиум продлён автоматически.');

                continue;
            }

            $premium->deactivate($user);
            $notifier->notify($user, 'ℹ️ Премиум-подписка завершена. Скидка снята.');
        }

        $this->info("Уведомлено: {$soon->count()}, деактивировано: {$expired->count()}");

        return self::SUCCESS;
    }
}
