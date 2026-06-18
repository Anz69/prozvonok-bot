<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_job_id')->constrained('check_jobs')->cascadeOnDelete();
            $table->string('phone', 20);                   // E.164
            // null (в обработке) | answered (🟢 дозвон) | no_answer (🔴 НДЗ)
            $table->string('status')->nullable();
            $table->string('operator')->nullable();        // реальный оператор (MNP)
            $table->string('mnp_operator')->nullable();    // оператор-донор / признак переноса
            $table->boolean('is_active')->nullable();      // статус активности номера
            $table->string('timezone')->nullable();        // часовой пояс абонента
            $table->string('last_status')->nullable();     // последний статус абонента
            $table->text('transcription')->nullable();     // транскрипция разговора
            $table->json('raw')->nullable();               // сырой ответ сервиса
            $table->timestamps();

            $table->index('check_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_numbers');
    }
};
