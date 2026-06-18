<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('required_channels', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id');                 // @username или -100... id канала
            $table->string('title')->nullable();
            $table->string('url')->nullable();         // ссылка для кнопки «Подписаться»
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('required_channels');
    }
};
