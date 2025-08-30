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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('currency_symbol')->nullable()->after('currency_code'); // الرمز مثل $ أو ر.س
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('currency_symbol')->nullable()->after('currency_code'); // الرمز مثل $ أو ر.س
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('currency_symbol');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('currency_symbol');
        });
    }
};
