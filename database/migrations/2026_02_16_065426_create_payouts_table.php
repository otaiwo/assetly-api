<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // vendor
            $table->decimal('amount', 12, 2); // amount to pay
            $table->string('status')->default('pending'); // pending/completed/failed
            $table->string('method')->nullable(); // stripe, paypal, bank
            $table->string('reference')->unique(); // optional for tracking
            $table->timestamp('processed_at')->nullable(); // when paid
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
