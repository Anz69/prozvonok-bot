<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // usdt (авто) | manager (подтверждение менеджером по диплинку)
            $table->string('method')->default('usdt')->after('uid');
            $table->foreignId('confirmed_by')->nullable()->after('tx_hash')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn('method');
        });
    }
};
