<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_user_id')->constrained('bot_users')->cascadeOnDelete();
            $table->string('uid')->unique();                  // публичный ID счёта
            $table->string('address');                        // адрес TRC-20 для приёма
            $table->string('network')->default('TRC20');
            $table->decimal('amount_expected', 20, 5);        // сумма к оплате, $
            $table->decimal('amount_received', 20, 5)->default(0);
            $table->decimal('bonus_amount', 20, 5)->default(0); // начисленный бонус лояльности
            // pending | paid | underpaid | overpaid | expired | cancelled
            $table->string('status')->default('pending');
            $table->string('tx_hash')->nullable()->unique();  // идемпотентность по хэшу
            $table->unsignedInteger('confirmations')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
