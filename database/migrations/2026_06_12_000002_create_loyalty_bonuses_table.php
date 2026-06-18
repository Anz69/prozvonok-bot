<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_bonuses', function (Blueprint $table) {
            $table->id();
            $table->decimal('threshold', 20, 5); // сумма пополнения, $
            $table->decimal('bonus', 20, 5);     // начисляемый бонус, $
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_bonuses');
    }
};
