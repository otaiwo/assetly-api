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

        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

        $table->string('provider'); // stripe, paystack, monnify
        $table->string('provider_reference')->unique();

        $table->decimal('amount', 10, 2);
        $table->string('currency')->default('USD');

        $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])
              ->default('pending');

        $table->json('metadata')->nullable();

        $table->timestamp('paid_at')->nullable();

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
