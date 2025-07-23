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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->decimal('total_price', 10, 2)->unsigned()->default(0);
            $table->decimal('discount')->unsigned()->default(0); // الخصم الإجمالي

            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
