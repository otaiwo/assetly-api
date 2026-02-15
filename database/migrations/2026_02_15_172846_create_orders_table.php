<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            // Financials
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('NGN');

            // Internal reference (your system ID)
            $table->uuid('internal_reference')->unique();

            // Payment Gateway Info
            $table->string('payment_gateway')->nullable();
            // paystack | stripe | moniepoint

            $table->string('gateway_reference')->nullable();
            $table->string('payment_method')->nullable();
            // card | bank_transfer | ussd | wallet

            // Status
            $table->string('status')->default('pending');
            // pending | paid | failed | cancelled

            // Payment timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();

            // Helpful indexes
            $table->index('user_id');
            $table->index('product_id');
            $table->index('status');
        });

        // PostgreSQL partial unique index
        DB::statement("
            CREATE UNIQUE INDEX unique_paid_order
            ON orders (user_id, product_id)
            WHERE status = 'paid'
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS unique_paid_order");

        Schema::dropIfExists('orders');
    }
};
