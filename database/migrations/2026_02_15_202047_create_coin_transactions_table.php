<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ==========================
        // COIN TRANSACTIONS TABLE
        // ==========================
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reference')->unique();
            $table->integer('amount');
            $table->enum('type', ['credit', 'debit'])->default('credit');
            $table->string('source')->nullable(); // stripe, paystack, daily_bonus
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamps();
        });

        // ==========================
        // USER COINS TABLE
        // ==========================
        Schema::create('user_coins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('amount');
            $table->enum('type', ['daily_bonus', 'recharge']);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('used')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_coins');
        Schema::dropIfExists('coin_transactions');
    }
};
