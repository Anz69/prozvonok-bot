<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_user_id')->constrained('bot_users')->cascadeOnDelete();
            // general | percent_request | history_request | withdraw_question | trial_premium
            $table->string('type')->default('general');
            $table->text('message')->nullable();
            $table->string('status')->default('open'); // open | closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
