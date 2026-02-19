<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('total_amount', 12, 2);
            $table->string('currency')->default('NGN');

            // Custom references (TEXT)
            $table->text('internal_reference')->unique();
            $table->text('payment_reference')->nullable()->unique();

            $table->string('payment_gateway')->nullable();
            $table->string('payment_method')->nullable();

            // Marketplace earnings support
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('seller_earnings', 12, 2)->default(0);

            $table->string('status')->default('pending');
            // pending | paid | failed | refunded | cancelled

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
