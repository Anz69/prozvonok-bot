<?php

namespace App\Services\Telegram;

use App\Models\BotUser;

/**
 * Валидация Telegram Mini App initData (https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app)
 * и резолв BotUser.
 */
class WebAppAuth
{
    /**
     * Проверяет подпись initData и возвращает массив полей (включая user), либо null.
     *
     * @return array<string, mixed>|null
     */
    public function validate(string $initData, int $ttlSeconds = 86400): ?array
    {
        if ($initData === '') {
            return null;
        }

        parse_str($initData, $params);
        $hash = $params['hash'] ?? null;
        if (! $hash) {
            return null;
        }
        unset($params['hash']);

        // data_check_string: пары key=value, отсортированные по ключу, через \n
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }
        $dataCheckString = implode("\n", $pairs);

        $token = (string) config('nutgram.token');
        if ($token === '') {
            return null;
        }

        $secretKey = hash_hmac('sha256', $token, 'WebAppData', true);
        $calculated = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (! hash_equals($calculated, (string) $hash)) {
            return null;
        }

        // TTL по auth_date
        $authDate = (int) ($params['auth_date'] ?? 0);
        if ($ttlSeconds > 0 && $authDate > 0 && (time() - $authDate) > $ttlSeconds) {
            return null;
        }

        if (isset($params['user'])) {
            $params['user'] = json_decode((string) $params['user'], true) ?: null;
        }

        return $params;
    }

    /**
     * Резолв/создание BotUser из проверенных данных initData.
     *
     * @param  array<string, mixed>  $data
     */
    public function resolveUser(array $data): ?BotUser
    {
        $tg = $data['user'] ?? null;
        if (! is_array($tg) || empty($tg['id'])) {
            return null;
        }

        $user = BotUser::firstOrNew(['telegram_id' => (int) $tg['id']]);
        $user->username = $tg['username'] ?? $user->username;
        $user->first_name = $tg['first_name'] ?? $user->first_name;
        $user->last_name = $tg['last_name'] ?? $user->last_name;
        $user->language_code = $tg['language_code'] ?? $user->language_code;
        if ($user->isDirty() || ! $user->exists) {
            $user->save();
        }

        return $user;
    }
}
