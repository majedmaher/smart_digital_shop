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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete(); // صاحب الكود
            $table->foreignId('referred_id')->nullable()->constrained('users')->nullOnDelete(); // المستخدم الذي سجل باستخدام الكود
            $table->string('code'); // كود الدعوة (يمكن استخدامه أكثر من مرة)
            $table->boolean('reward_given')->default(false); // هل تم إضافة النقاط
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
