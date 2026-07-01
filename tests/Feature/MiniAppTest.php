<?php

namespace Tests\Feature;

use App\Models\BotUser;
use App\Models\Geo;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MiniAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(array $attrs = []): BotUser
    {
        return BotUser::create(array_merge([
            'telegram_id' => 5551234,
            'first_name' => 'Mini',
        ], $attrs));
    }

    /** Авторизованный запрос через сессию мини-аппа. */
    private function asUser(BotUser $user): static
    {
        return $this->withSession(['mini_bot_user_id' => $user->id]);
    }

    public function test_gate_returns_boot_page_without_session(): void
    {
        // testing-окружение (не local) → без сессии отдаётся boot-страница, не 200-дашборд.
        $res = $this->get('/app');
        $res->assertOk();
        $res->assertSee('telegram-web-app.js', false);
    }

    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $user = $this->user();
        $this->asUser($user)->get('/app')
            ->assertOk()
            ->assertSee('data-page="app"', false);
    }

    public function test_topup_creates_manager_payment_and_returns_link(): void
    {
        Setting::put('min_deposit', '10');
        $user = $this->user();

        $res = $this->asUser($user)->postJson('/app/topup', ['amount' => 100]);

        $res->assertOk()
            ->assertJsonStructure(['amount', 'manager_url', 'deep_link']);

        $this->assertDatabaseHas('payments', [
            'bot_user_id' => $user->id,
            'method' => Payment::METHOD_MANAGER,
            'amount_expected' => '100.00000',
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function test_topup_below_minimum_is_rejected(): void
    {
        Setting::put('min_deposit', '10');
        $user = $this->user();

        $this->asUser($user)->postJson('/app/topup', ['amount' => 1])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_check_quote_parses_file_and_returns_cost(): void
    {
        $user = $this->user();
        $geo = Geo::active()->first();
        $this->assertNotNull($geo, 'Сидер должен создать хотя бы одно ГЕО');

        $content = "+7 (900) 123-45-67\n89001234568\n9001234569\n";
        $file = UploadedFile::fake()->createWithContent('base.txt', $content);

        $res = $this->asUser($user)->post('/app/check/quote', [
            'geo' => $geo->code,
            'file' => $file,
        ]);

        $res->assertOk()
            ->assertJsonStructure(['token', 'geo', 'total', 'valid', 'free', 'discount', 'cost', 'balance']);
        $this->assertSame($geo->code, $res->json('geo'));
        $this->assertGreaterThan(0, $res->json('valid'));
    }

    public function test_check_quote_rejects_file_without_numbers(): void
    {
        $user = $this->user();
        $geo = Geo::active()->first();

        $file = UploadedFile::fake()->createWithContent('junk.txt', "мусор\nбез\nномеров\n");

        $this->asUser($user)->post('/app/check/quote', [
            'geo' => $geo->code,
            'file' => $file,
        ])->assertStatus(422)->assertJsonStructure(['message']);
    }

    public function test_faq_page_renders_with_seeded_items(): void
    {
        $user = $this->user();
        $this->asUser($user)->get('/app/faq')
            ->assertOk()
            ->assertSee('data-page="app"', false);

        // Сидер кладёт непустой FAQ
        $this->assertNotEmpty(Setting::get('faq_items', []));
    }

    public function test_premium_page_renders(): void
    {
        $user = $this->user();
        $this->asUser($user)->get('/app/premium')
            ->assertOk()
            ->assertSee('data-page="app"', false);
    }

    public function test_premium_activation_charges_deposit(): void
    {
        Setting::put('premium_price', '250', 'float');
        Setting::put('premium_discount', '25', 'int');
        Setting::put('premium_days', '30', 'int');

        $user = $this->user(['deposit_balance' => 300]);

        $res = $this->asUser($user)->postJson('/app/premium/activate', ['tier' => 'premium']);
        $res->assertOk()->assertJson(['ok' => true, 'tier' => 'premium', 'discount' => 25]);

        $user->refresh();
        $this->assertSame('premium', $user->premium_tier);
        $this->assertSame(25, (int) $user->check_discount);
        $this->assertSame('50.00000', $user->deposit_balance); // 300 - 250
    }

    public function test_premium_activation_without_funds_is_rejected(): void
    {
        Setting::put('premium_price', '250', 'float');
        $user = $this->user(['deposit_balance' => 10]);

        $this->asUser($user)->postJson('/app/premium/activate', ['tier' => 'premium'])
            ->assertStatus(422)
            ->assertJson(['need_topup' => true]);

        $this->assertNull($user->fresh()->premium_tier);
    }

    public function test_routes_require_authentication(): void
    {
        // Без сессии POST-действия не должны проходить как авторизованные:
        // gate отдаёт boot-страницу (200, не JSON-успех) вместо создания платежа.
        $this->postJson('/app/topup', ['amount' => 100]);
        $this->assertDatabaseCount('payments', 0);
    }
}
