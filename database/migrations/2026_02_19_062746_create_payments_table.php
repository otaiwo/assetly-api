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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            // Optional for subscription-based products
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();

            // Payment provider info
            $table->string('provider'); // stripe, paystack, monnify
            $table->string('provider_reference')->unique(); // reference from gateway
            $table->string('payment_method')->nullable(); // card, bank_transfer, ussd, wallet

            // Financials
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('NGN');

            // Status
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])
                  ->default('pending');

            // Optional metadata
            $table->json('metadata')->nullable();

            // Payment timestamp
            $table->timestamp('paid_at')->nullable();

            // Internal references for custom logic
            $table->text('internal_reference')->nullable()->unique();
            $table->text('payment_reference')->nullable()->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
