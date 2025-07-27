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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('payment_provider', ['paymob', 'stripe', 'moyasar', 'stc_pay'])->default('paymob');
            $table->string('reference')->nullable(); // transaction ID من بوابة الدفع
            $table->string('parent_transaction_id')->nullable();
            $table->index('parent_transaction_id');

            $table->string('payment_intention_id')->nullable(); // من paymob أو stripe

            $table->enum('currency', ['SAR'])->default('SAR');
            $table->integer('amount_cents'); // المبلغ هللة أو سنت

            $table->enum('status', ['pending', 'paid', 'refunded', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable(); // اختياري لو عملت استرجاع

            $table->json('raw_response')->nullable(); // الرد من بوابة الدفع

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
