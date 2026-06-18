<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_user_id')->constrained('bot_users')->cascadeOnDelete();
            $table->decimal('amount', 20, 5);
            $table->string('address');                 // адрес USDT TRC-20 получателя
            $table->string('network')->default('TRC20');
            // pending | approved | rejected | paid
            $table->string('status')->default('pending');
            $table->string('tx_hash')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();      // причина отклонения
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
