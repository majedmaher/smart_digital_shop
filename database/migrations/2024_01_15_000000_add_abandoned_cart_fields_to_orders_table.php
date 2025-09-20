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
            $table->timestamp('last_abandoned_notification_at')->nullable()->after('updated_at');
            $table->integer('abandoned_notifications_count')->default(0)->after('last_abandoned_notification_at');
            $table->timestamp('recovered_at')->nullable()->after('abandoned_notifications_count');
            $table->string('recovery_source')->nullable()->after('recovered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'last_abandoned_notification_at',
                'abandoned_notifications_count',
                'recovered_at',
                'recovery_source'
            ]);
        });
    }
};
