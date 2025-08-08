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
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('total_price_user_currency', 8, 2)->nullable()->after('total');
            $table->decimal('discount_user_currency', 8, 2)->nullable()->after('discount');
            $table->string('currency_code', 10)->nullable()->after('total_price_user_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['total_price_user_currency', 'discount_user_currency', 'currency_code']);
        });
    }
};
