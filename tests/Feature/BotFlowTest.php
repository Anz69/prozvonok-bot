<?php

namespace Tests\Feature;

use App\Models\BotUser;
use App\Models\Geo;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\UpdateType;
use Tests\TestCase;

/**
 * Сквозная проверка бота (режим одного экрана): онбординг, навигация nav:/act:,
 * калькулятор и старт проверки базы. Гоняем реальные хендлеры из routes/telegram.php.
 */
class BotFlowTest extends TestCase
{
    use RefreshDatabase;

    private const UID = 555;

    /** @var \SergiX44\Nutgram\Testing\FakeNutgram */
    private Nutgram $bot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();

        $this->bot = app(Nutgram::class);
        $this->bot->willStartConversation();
    }

    private function text(string $value): void
    {
        $this->bot->hearUpdateType(UpdateType::MESSAGE, [
            'from' => ['id' => self::UID, 'is_bot' => false, 'first_name' => 'Test'],
            'chat' => ['id' => self::UID, 'type' => 'private'],
            'text' => $value,
        ])->reply();
    }

    private function cb(string $data): void
    {
        $this->bot->hearUpdateType(UpdateType::CALLBACK_QUERY, [
            'from' => ['id' => self::UID, 'is_bot' => false, 'first_name' => 'Test'],
            'message' => [
                'message_id' => 1,
                'date' => 1700000000,
                'chat' => ['id' => self::UID, 'type' => 'private'],
            ],
            'data' => $data,
        ])->reply();
    }

    private function disableOnboarding(): void
    {
        Setting::put('subscription_required', '0', 'bool');
        Setting::put('captcha_enabled', '0', 'bool');
        Cache::flush();
    }

    public function test_start_creates_user_and_runs_onboarding(): void
    {
        $this->text('/start');

        $this->assertDatabaseHas('bot_users', ['telegram_id' => self::UID]);
        $this->bot->assertCalled('getChatMember'); // проверка подписки запустилась
    }

    public function test_home_and_inline_navigation_edit_single_screen(): void
    {
        $this->disableOnboarding();

        $this->text('/start');           // главный экран отправлен
        $methods = array_map(fn ($rr) => array_values($rr)[0]->getUri()->getPath(), $this->bot->getRequestHistory());
        $this->assertContains('sendMessage', $methods);

        // Навигация по инлайн-кнопкам редактирует то же сообщение
        $this->cb('nav:profile');
        $this->cb('nav:balance');
        $this->cb('nav:home');
        $this->bot->assertCalled('editMessageText');
    }

    public function test_calculator_flow(): void
    {
        $this->disableOnboarding();
        $this->text('/start');

        $this->cb('act:calculator');
        $this->bot->assertActiveConversation(self::UID, self::UID);

        $this->cb('calc:geo:RU');
        $this->text('5000'); // расчёт
        $this->bot->assertCalled('sendMessage');
    }

    public function test_check_base_geo_selection(): void
    {
        $this->disableOnboarding();
        $this->text('/start');

        $this->cb('act:check_base');
        $this->bot->assertActiveConversation(self::UID, self::UID);

        $this->cb('check:geo:RU');
        $this->assertTrue(Geo::where('code', 'RU')->exists());
    }

    public function test_bottom_keyboard_back_and_home(): void
    {
        $this->disableOnboarding();
        $this->text('/start');

        $this->cb('nav:profile');        // ушли в профиль (screen=profile)
        $this->text('⬅️ Назад');         // нижняя кнопка → на главную
        $this->assertSame('home', BotUser::firstOrFail()->fresh()->state['screen'] ?? null);

        $this->cb('nav:balance');
        $this->text('🏠 Главная');        // нижняя кнопка → главная
        $this->assertSame('home', BotUser::firstOrFail()->fresh()->state['screen'] ?? null);
    }

    public function test_nav_home_escapes_active_conversation(): void
    {
        $this->disableOnboarding();
        $this->text('/start');

        $this->cb('act:calculator');
        $this->bot->assertActiveConversation(self::UID, self::UID);

        // 🏠 Главная во время диалога — выходим из него
        $this->cb('nav:home');
        $this->bot->assertNoConversation(self::UID, self::UID);
    }
}
