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
            $table->boolean('synced_to_zoho')->default(false);
            $table->timestamp('zoho_synced_at')->nullable();
            $table->string('zoho_invoice_id')->nullable();
            $table->string('zoho_payment_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('synced_to_zoho');
            $table->dropColumn('zoho_synced_at');
            $table->dropColumn('zoho_invoice_id');
            $table->dropColumn('zoho_payment_id');
        });
    }
};
