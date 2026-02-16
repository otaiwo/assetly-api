<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
$table->foreignId('user_id')->constrained()->onDelete('cascade');
$table->foreignId('category_id')->constrained()->onDelete('cascade');
$table->string('name');
$table->text('description')->nullable();
$table->decimal('price', 10, 2)->default(0);
$table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
$table->enum('type', ['free', 'pro'])->default('pro');
$table->integer('credit_cost')->default(0);
$table->string('file_path')->nullable(); // new column for downloads
$table->string('image')->nullable();
$table->timestamps();
$table->softDeletes();

        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
