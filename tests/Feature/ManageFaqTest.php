<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageFaq;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageFaqTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'a@dozvon.local', 'password' => 'secret', 'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_only_admin_can_access(): void
    {
        $this->actingAs($this->admin());
        $this->assertTrue(ManageFaq::canAccess());

        $manager = User::create([
            'name' => 'M', 'email' => 'm@dozvon.local', 'password' => 'secret', 'role' => User::ROLE_MANAGER,
        ]);
        $this->actingAs($manager);
        $this->assertFalse(ManageFaq::canAccess());
    }

    public function test_page_renders_and_saves_faq(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ManageFaq::class)
            ->assertOk()
            ->set('data.items', [
                ['q' => 'Вопрос 1', 'a' => 'Ответ 1'],
                ['q' => 'Вопрос 2', 'a' => 'Ответ 2'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $items = Setting::get('faq_items', []);
        $this->assertCount(2, $items);
        $this->assertSame('Вопрос 1', $items[0]['q']);
        $this->assertSame('Ответ 2', $items[1]['a']);

        // Пустые строки обязательны к заполнению — пустой вопрос отклоняется валидацией.
        Livewire::test(ManageFaq::class)
            ->set('data.items', [['q' => '', 'a' => 'нет вопроса']])
            ->call('save')
            ->assertHasErrors('data.items.0.q');
    }
}
