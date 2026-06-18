<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_user_id')->constrained('bot_users')->cascadeOnDelete();
            // deposit | charge | bonus_loyalty | referral_commission | referral_bonus
            // | withdraw | premium_charge | admin_adjust | refund
            $table->string('type');
            $table->string('wallet')->default('deposit'); // deposit | referral — какой баланс затронут
            $table->decimal('amount', 20, 5);             // знаковая: + пополнение, - списание
            $table->decimal('balance_after', 20, 5);
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->nullableMorphs('sourceable');         // payment / check_job / withdrawal
            $table->timestamps();

            $table->index(['bot_user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
