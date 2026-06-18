<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geos', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();          // RU / KZ / BY
            $table->string('name');
            $table->string('flag', 8)->nullable();         // 🇷🇺
            $table->decimal('price_per_1000', 20, 5);      // тариф за 1000 номеров, $
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geos');
    }
};
