<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zvonok_callbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_job_id')->nullable()->constrained('check_jobs')->nullOnDelete();
            $table->foreignId('check_number_id')->nullable()->constrained('check_numbers')->nullOnDelete();
            $table->json('payload');                   // сырой postback для аудита/повторной обработки
            $table->boolean('processed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zvonok_callbacks');
    }
};
