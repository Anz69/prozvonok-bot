<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_texts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('content');
            $table->string('description')->nullable();   // подсказка админу
            $table->json('placeholders')->nullable();    // список доступных {плейсхолдеров}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_texts');
    }
};
