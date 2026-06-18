<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            // +% к первому пополнению приведённого пользователя (A.1) — разовый
            $table->boolean('first_topup_bonus_paid')->default(false)->after('first_deposit_bonus_paid');
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn('first_topup_bonus_paid');
        });
    }
};
