<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_buttons', function (Blueprint $table) {
            // Номер ряда в нижнем меню: кнопки одного ряда располагаются рядом
            $table->unsignedSmallInteger('row')->default(1)->after('menu');
        });
    }

    public function down(): void
    {
        Schema::table('bot_buttons', function (Blueprint $table) {
            $table->dropColumn('row');
        });
    }
};
