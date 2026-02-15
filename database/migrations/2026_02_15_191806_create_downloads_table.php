<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('guest_id')->nullable();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('ip_address')->nullable();
            $table->string('device_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for faster lookups
            $table->index('guest_id');
            $table->index('ip_address');
            $table->index('user_id');
            $table->index('product_id');
            $table->index(['guest_id', 'product_id', 'created_at']); // for monthly counts
        });
    }

    public function down()
    {
        Schema::dropIfExists('downloads');
    }
};
