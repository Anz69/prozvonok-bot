<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@dozvon.local')],
            [
                'name' => 'Администратор',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => User::ROLE_ADMIN,
            ],
        );
    }
}
