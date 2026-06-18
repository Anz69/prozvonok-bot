<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_id')->unique();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('language_code', 8)->nullable();
            $table->string('timezone')->default('Europe/Moscow'); // для расчёта рабочих часов

            // Финансы (5 знаков после запятой, как в исходном боте)
            $table->decimal('deposit_balance', 20, 5)->default(0);
            $table->decimal('referral_balance', 20, 5)->default(0);
            $table->decimal('total_deposited', 20, 5)->default(0);

            // Реферальная система / премиум
            $table->unsignedTinyInteger('referral_percent')->default(5);
            $table->unsignedTinyInteger('check_discount')->default(0); // %, от премиума
            $table->foreignId('referrer_id')->nullable()->constrained('bot_users')->nullOnDelete();
            $table->string('premium_tier')->nullable();      // premium | premium_plus
            $table->timestamp('premium_until')->nullable();
            $table->boolean('premium_auto_renew')->default(false);
            $table->boolean('withdraw_unlocked')->default(false); // ручная разблокировка админом

            // Онбординг / акции
            $table->boolean('is_subscribed')->default(false);
            $table->boolean('passed_captcha')->default(false);
            $table->boolean('used_free_numbers')->default(false); // акция «первые N бесплатно» — разово

            // Статистика
            $table->unsignedBigInteger('numbers_checked')->default(0);
            $table->unsignedBigInteger('numbers_answered')->default(0); // дозвон
            $table->unsignedBigInteger('numbers_failed')->default(0);   // НДЗ
            $table->unsignedInteger('files_checked')->default(0);

            // Сервисное состояние диалога (выбранное ГЕО и т.п.)
            $table->json('state')->nullable();
            $table->boolean('is_banned')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_users');
    }
};
