<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('bot_users')->cascadeOnDelete();
            $table->foreignId('referred_id')->unique()->constrained('bot_users')->cascadeOnDelete();
            $table->boolean('first_deposit_bonus_paid')->default(false); // +10$ за депозит от 100$
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
