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
        // database/migrations/xxxx_xx_xx_add_vat_to_products.php
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->default(0)->after('discount'); // نسبة % VAT
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('vat', 10, 2)->unsigned()->default(0)->after('discount_user_currency'); // خصم هذا العنصر
            $table->decimal('vat_user_currency', 10, 2)->unsigned()->default(0)->after('vat'); // خصم هذا العنصر

        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('vat', 10, 2)->unsigned()->default(0)->after('discount_user_currency'); // خصم هذا العنصر
            $table->decimal('vat_user_currency', 10, 2)->unsigned()->default(0)->after('vat'); // خصم هذا العنصر
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('vat_rate');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('vat');
            $table->dropColumn('vat_user_currency');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('vat');
            $table->dropColumn('vat_user_currency');
        });
    }
};
