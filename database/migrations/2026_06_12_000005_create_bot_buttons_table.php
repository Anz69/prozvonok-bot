<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_buttons', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('menu')->default('main');   // к какому меню относится
            $table->string('action');                  // машинное действие (check_base, profile, ...)
            $table->string('payload')->nullable();     // доп. данные / url
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_buttons');
    }
};
