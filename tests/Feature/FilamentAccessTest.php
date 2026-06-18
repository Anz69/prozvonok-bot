<?php

namespace Tests\Feature;

use App\Filament\Resources\Settings\SettingResource;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_sees_content_resources_manager_sees_operational(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'a@dozvon.local', 'password' => 'secret', 'role' => User::ROLE_ADMIN,
        ]);
        $manager = User::create([
            'name' => 'Manager', 'email' => 'm@dozvon.local', 'password' => 'secret', 'role' => User::ROLE_MANAGER,
        ]);

        // Контентный ресурс (Настройки) — только админу
        $this->actingAs($admin);
        $this->assertTrue(SettingResource::canAccess());

        $this->actingAs($manager);
        $this->assertFalse(SettingResource::canAccess());

        // Операционный ресурс (Транзакции) доступен и менеджеру
        $this->assertTrue(TransactionResource::canAccess());
    }
}
