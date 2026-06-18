<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_user_id')->constrained('bot_users')->cascadeOnDelete();
            $table->string('geo_code', 2);                 // RU / KZ / BY
            $table->unsignedInteger('numbers_total')->default(0);
            $table->unsignedInteger('numbers_valid')->default(0);
            $table->decimal('cost', 20, 5)->default(0);
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->unsignedInteger('free_applied')->default(0); // сколько номеров по акции бесплатно
            // pending | queued | scheduled | processing | completed | failed | cancelled
            $table->string('status')->default('pending');
            $table->string('input_path')->nullable();      // исходный файл
            $table->string('output_path')->nullable();     // результирующий файл
            $table->json('summary')->nullable();           // сводка по статусам
            $table->string('zvonok_campaign_id')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('scheduled_for')->nullable(); // отложен до начала рабочих часов
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['bot_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_jobs');
    }
};
