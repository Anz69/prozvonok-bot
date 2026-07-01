<?php

namespace Tests\Unit;

use App\Services\Telegram\WebAppAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAppAuthTest extends TestCase
{
    use RefreshDatabase;

    /** Собирает корректный initData с валидной подписью под тестовый токен. */
    private function signedInitData(array $overrides = [], ?int $authDate = null): string
    {
        $token = (string) config('nutgram.token');
        $authDate ??= time();

        $params = array_merge([
            'auth_date' => (string) $authDate,
            'query_id' => 'AAH123',
            'user' => json_encode([
                'id' => 5550777,
                'first_name' => 'Web',
                'last_name' => 'App',
                'username' => 'webapp',
                'language_code' => 'ru',
            ], JSON_UNESCAPED_UNICODE),
        ], $overrides);

        ksort($params);
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs[] = $k . '=' . $v;
        }
        $secretKey = hash_hmac('sha256', $token, 'WebAppData', true);
        $hash = hash_hmac('sha256', implode("\n", $pairs), $secretKey);

        return http_build_query($params + ['hash' => $hash]);
    }

    public function test_valid_init_data_passes_and_decodes_user(): void
    {
        $data = (new WebAppAuth())->validate($this->signedInitData());

        $this->assertIsArray($data);
        $this->assertSame(5550777, $data['user']['id']);
        $this->assertSame('webapp', $data['user']['username']);
    }

    public function test_tampered_hash_is_rejected(): void
    {
        $initData = $this->signedInitData();
        $tampered = preg_replace('/hash=[0-9a-f]+/', 'hash=deadbeef', $initData);

        $this->assertNull((new WebAppAuth())->validate($tampered));
    }

    public function test_modified_payload_breaks_signature(): void
    {
        // Меняем user после подписи — подпись больше не совпадает.
        $initData = $this->signedInitData();
        $forged = str_replace('5550777', '999999', $initData);

        $this->assertNull((new WebAppAuth())->validate($forged));
    }

    public function test_expired_auth_date_is_rejected(): void
    {
        $old = $this->signedInitData(authDate: time() - 100000);

        $this->assertNull((new WebAppAuth())->validate($old, 86400));
    }

    public function test_empty_init_data_is_null(): void
    {
        $this->assertNull((new WebAppAuth())->validate(''));
    }

    public function test_resolve_user_creates_bot_user(): void
    {
        $auth = new WebAppAuth();
        $data = $auth->validate($this->signedInitData());

        $user = $auth->resolveUser($data);

        $this->assertNotNull($user);
        $this->assertSame(5550777, $user->telegram_id);
        $this->assertSame('Web', $user->first_name);
        $this->assertDatabaseHas('bot_users', ['telegram_id' => 5550777]);
    }
}
