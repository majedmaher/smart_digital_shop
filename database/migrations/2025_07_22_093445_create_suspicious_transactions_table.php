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
        Schema::create('suspicious_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('risk_score')->default(0);
            $table->json('risk_factors')->nullable();
            $table->string('user_ip')->nullable();
            $table->string('user_country', 2)->nullable(); // ISO country code
            $table->string('card_country', 2)->nullable(); // ISO country code
            $table->bigInteger('amount_cents');
            $table->enum('status', ['pending_review', 'approved', 'blocked'])->default('pending_review');
            $table->timestamp('analyzed_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['status', 'created_at']);
            $table->index(['risk_score', 'created_at']);
            $table->index(['user_country', 'card_country']);
            $table->index('analyzed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspicious_transactions');
    }
};

