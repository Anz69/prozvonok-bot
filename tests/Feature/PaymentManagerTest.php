<?php

namespace Tests\Feature;

use App\Models\BotUser;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\UpdateType;
use Tests\TestCase;

class PaymentManagerTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN = 999000111;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Setting::put('admin_chat_ids', json_encode([self::ADMIN]), 'json');
        Setting::put('subscription_required', '0', 'bool');
        Cache::flush();
    }

    private function start(int $fromId, string $payload): \SergiX44\Nutgram\Testing\FakeNutgram
    {
        /** @var \SergiX44\Nutgram\Testing\FakeNutgram $bot */
        $bot = app(Nutgram::class);
        $bot->hearUpdateType(UpdateType::MESSAGE, [
            'from' => ['id' => $fromId, 'is_bot' => false, 'first_name' => 'X'],
            'chat' => ['id' => $fromId, 'type' => 'private'],
            'text' => trim("/start {$payload}"),
        ])->reply();

        return $bot;
    }

    public function test_admin_start_opens_admin_panel(): void
    {
        $bot = $this->start(self::ADMIN, '');

        $found = false;
        foreach ($bot->getRequestHistory() as $rr) {
            if (str_contains((string) array_values($rr)[0]->getBody(), 'Админка')) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'Админу /start должен открывать админку');
    }

    private function tap(int $fromId, string $data): void
    {
        app(Nutgram::class)->hearUpdateType(UpdateType::CALLBACK_QUERY, [
            'from' => ['id' => $fromId, 'is_bot' => false, 'first_name' => 'X'],
            'message' => ['message_id' => 1, 'date' => 1700000000, 'chat' => ['id' => $fromId, 'type' => 'private']],
            'data' => $data,
        ])->reply();
    }

    public function test_confirm_manual_service_credits(): void
    {
        [$user, $payment] = $this->makePayment();
        app(\App\Services\Usdt\PaymentService::class)->confirmManual($payment);
        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame('100.00000', $user->fresh()->deposit_balance);
    }

    private function makePayment(): array
    {
        $user = BotUser::create(['telegram_id' => 5550001, 'first_name' => 'Payer']);
        $payment = Payment::create([
            'bot_user_id' => $user->id,
            'uid' => 'TESTHASH123',
            'method' => Payment::METHOD_MANAGER,
            'amount_expected' => 100,
            'status' => Payment::STATUS_PENDING,
        ]);

        return [$user, $payment];
    }

    public function test_admin_confirm_flow_credits_user(): void
    {
        [$user, $payment] = $this->makePayment();
        $this->assertTrue(\App\Telegram\Support\Admin::is(self::ADMIN));

        // 1) Админ открывает диплинк → подтверждение, НЕ зачисляется сразу
        $this->start(self::ADMIN, 'pay_TESTHASH123');
        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame('0.00000', $user->fresh()->deposit_balance);

        // 2) Админ жмёт «✅ Подтвердить» → зачисление
        $bot = app(Nutgram::class);
        $this->tap(self::ADMIN, 'paycfm:TESTHASH123');
        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame('100.00000', $user->fresh()->deposit_balance);

        // Плательщику ушло ≥2 сообщения: уведомление + перепост управления вниз
        $toPayer = 0;
        foreach ($bot->getRequestHistory() as $rr) {
            $req = array_values($rr)[0];
            if ($req->getUri()->getPath() === 'sendMessage' && str_contains((string) $req->getBody(), (string) $user->telegram_id)) {
                $toPayer++;
            }
        }
        $this->assertGreaterThanOrEqual(2, $toPayer);
    }

    public function test_admin_cancel_does_not_credit(): void
    {
        [$user, $payment] = $this->makePayment();

        $this->start(self::ADMIN, 'pay_TESTHASH123');
        $this->tap(self::ADMIN, 'paycxl:TESTHASH123');

        $this->assertSame(Payment::STATUS_CANCELLED, $payment->fresh()->status);
        $this->assertSame('0.00000', $user->fresh()->deposit_balance);
    }

    public function test_non_admin_opening_pay_link_does_not_credit(): void
    {
        [$user, $payment] = $this->makePayment();

        $this->start(5550001, 'pay_TESTHASH123'); // сам пользователь, не админ

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame('0.00000', $user->fresh()->deposit_balance);
    }
}
