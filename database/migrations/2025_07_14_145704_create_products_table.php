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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id');
            $table->foreignId('category_id')->nullable()->constrained('categories', 'id');
            $table->foreignId('sub_category_id')->constrained('sub_categories', 'id')->cascadeOnDelete()->cascadeOnUpdate();
            $table->json('title');
            $table->json('content');
            $table->json('description');
            $table->string('image');
            $table->decimal('price_before', 8, 2)->nullable()->unsigned();
            $table->decimal('price', 8, 2)->unsigned();
            $table->tinyInteger('discount')->nullable()->unsigned();
            $table->enum('shipping_payment', ['code', 'account', 'manual']);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
