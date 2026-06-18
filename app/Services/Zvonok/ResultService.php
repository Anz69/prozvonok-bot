<?php

namespace App\Services\Zvonok;

use App\Models\BotText;
use App\Models\CheckJob;
use App\Models\CheckNumber;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\BalanceService;
use App\Telegram\Support\Notifier;
use Illuminate\Support\Facades\Storage;

/**
 * Применение результатов обзвона к check_numbers, агрегация сводки и выдача файла.
 * Используется и polling-ом, и postback-вебхуком.
 */
class ResultService
{
    public function __construct(
        private readonly CheckResultExporter $exporter,
        private readonly Notifier $notifier,
        private readonly BalanceService $balance,
    ) {
    }

    /**
     * Возврат за необработанные номера (раздел 8.1): при charge_policy=answered
     * возвращаем долю стоимости за НДЗ на депозит.
     */
    private function refundUnbilled(CheckJob $job, int $answered, int $failed, int $total): void
    {
        if (Setting::get('charge_policy', 'all') !== 'answered' || $total === 0 || $failed === 0) {
            return;
        }

        $refund = round((float) $job->cost * $failed / $total, 5);
        if ($refund <= 0) {
            return;
        }

        $this->balance->credit(
            $job->botUser,
            $refund,
            Transaction::TYPE_REFUND,
            Transaction::WALLET_DEPOSIT,
            "Возврат за необработанные номера задания #{$job->id} ({$failed} из {$total})",
            $job,
        );
    }

    /**
     * Применить пакет результатов (ключ — телефон E.164).
     *
     * @param  array<string, array<string, mixed>>  $results
     */
    public function applyResults(CheckJob $job, array $results): void
    {
        foreach ($results as $phone => $data) {
            $this->applyOne($job, (string) $phone, $data);
        }

        $this->finalizeIfComplete($job);
    }

    /**
     * Применить результат к одному номеру задания.
     *
     * @param  array<string, mixed>  $data
     */
    public function applyOne(CheckJob $job, string $phone, array $data): void
    {
        $number = $job->numbers()->where('phone', $phone)->first();
        if ($number === null) {
            return;
        }

        $number->update([
            'status' => $data['status'] ?? null,
            'operator' => $data['operator'] ?? null,
            'mnp_operator' => $data['mnp_operator'] ?? null,
            'is_active' => $data['is_active'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'last_status' => $data['last_status'] ?? null,
            'transcription' => $data['transcription'] ?? null,
            'raw' => $data['raw'] ?? null,
        ]);
    }

    /**
     * Если все номера задания получили статус — агрегировать, выгрузить файл и отправить.
     */
    public function finalizeIfComplete(CheckJob $job): void
    {
        // Защита от повторной финализации (двойной возврат/повторная отправка файла)
        if ($job->status === CheckJob::STATUS_COMPLETED) {
            return;
        }

        $pending = $job->numbers()->whereNull('status')->exists();
        if ($pending) {
            return;
        }

        $answered = (int) $job->numbers()->where('status', CheckNumber::STATUS_ANSWERED)->count();
        $failed = (int) $job->numbers()->where('status', CheckNumber::STATUS_NO_ANSWER)->count();
        $total = $answered + $failed;

        $summary = ['answered' => $answered, 'failed' => $failed, 'total' => $total];

        // Выгрузка файла
        $relative = $this->exporter->export($job);

        $job->update([
            'status' => CheckJob::STATUS_COMPLETED,
            'summary' => $summary,
            'output_path' => $relative,
            'completed_at' => now(),
        ]);

        // Возврат за необработанные (раздел 8.1): при политике «answered» возвращаем долю за НДЗ
        $this->refundUnbilled($job, $answered, $failed, $total);

        // Статистика пользователя
        $user = $job->botUser;
        $user->increment('numbers_checked', $total);
        $user->increment('numbers_answered', $answered);
        $user->increment('numbers_failed', $failed);

        // Отправка файла + сводка
        $caption = BotText::render('check_completed', [
            'total' => $total,
            'answered' => $answered,
            'failed' => $failed,
            'geo' => $job->geo_code,
        ], default: "✅ Проверка завершена: 🟢 {$answered} / 🔴 {$failed} из {$total}.");

        $this->notifier->sendDocument($user, Storage::disk('local')->path($relative), $caption);
    }
}
