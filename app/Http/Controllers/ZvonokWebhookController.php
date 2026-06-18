<?php

namespace App\Http\Controllers;

use App\Models\CheckJob;
use App\Models\CheckNumber;
use App\Models\Setting;
use App\Models\ZvonokCallback;
use App\Services\Zvonok\ResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Приём postback'ов от Звонок.com при смене статуса звонка (раздел 4.3).
 * Защита — секретным токеном в URL. Сырьё пишем в zvonok_callbacks для аудита.
 */
class ZvonokWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret, ResultService $results)
    {
        if (! hash_equals((string) config('dozvon.zvonok.postback_secret'), $secret)) {
            abort(403);
        }

        $payload = $request->all();
        // Звонок.com шлёт postback с префиксом ct_ (ct_phone, ct_status, ct_dial_status, ...)
        $phone = (string) ($request->input('ct_phone') ?? $request->input('phone') ?? '');

        // Находим номер задания по телефону (среди активных заданий)
        $number = CheckNumber::where('phone', $phone)
            ->whereHas('checkJob', fn ($q) => $q->where('status', CheckJob::STATUS_PROCESSING))
            ->latest()->first();

        ZvonokCallback::create([
            'check_job_id' => $number?->check_job_id,
            'check_number_id' => $number?->id,
            'payload' => $payload,
            'processed' => $number !== null,
        ]);

        if ($number === null) {
            Log::channel('integration')->warning('Zvonok postback: номер не найден', ['phone' => $phone]);

            return response()->json(['ok' => true]);
        }

        $job = $number->checkJob;
        $map = (array) Setting::get('zvonok_status_map', []);
        $rawStatus = (string) ($request->input('ct_status') ?? $request->input('status') ?? '');
        $mapped = $map[$rawStatus] ?? null;

        if ($mapped === null) {
            // ещё не финальный статус (in_process) — фиксируем сырьё, номер оставляем в очереди
            return response()->json(['ok' => true]);
        }

        $results->applyOne($job, $phone, [
            'status' => $mapped,
            'operator' => $request->input('ct_phone_oper') ?? $request->input('operator'),
            'mnp_operator' => $request->input('ct_mnp_oper'),
            'is_active' => $mapped === CheckNumber::STATUS_ANSWERED,
            'timezone' => $request->input('ct_phone_region'),
            'last_status' => $rawStatus,
            'transcription' => $request->input('ct_recognized_speech') ?? $request->input('ct_data'),
            'raw' => $payload,
        ]);

        $results->finalizeIfComplete($job);

        return response()->json(['ok' => true]);
    }
}
