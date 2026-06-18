<?php

namespace App\Console\Commands;

use App\Services\Usdt\PaymentService;
use Illuminate\Console\Command;

/**
 * Опрос входящих USDT-платежей и авто-зачисление (раздел 3.4). В расписании — каждую минуту.
 */
class PollPayments extends Command
{
    protected $signature = 'payments:poll';

    protected $description = 'Опросить сеть USDT TRC-20 и зачислить подтверждённые пополнения';

    public function handle(PaymentService $payments): int
    {
        $credited = $payments->pollAll();
        $this->info("Зачислено платежей: {$credited}");

        return self::SUCCESS;
    }
}
