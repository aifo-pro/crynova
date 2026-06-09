<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('merchant_id')->constrained();
            $table->foreignId('currency_id')->constrained();

            $table->decimal('amount', 36, 18);
            $table->decimal('fee', 36, 18)->default(0);    // network fee deducted from merchant balance
            $table->decimal('amount_sent', 36, 18)->default(0);

            $table->string('to_address', 100);
            $table->string('memo', 100)->nullable();

            $table->enum('status', [
                'pending',    // submitted, awaiting admin approval
                'approved',   // admin approved, queued for broadcast
                'processing', // transaction being built & signed
                'sent',       // broadcast to network
                'confirmed',  // enough block confirmations
                'failed',     // error during send
                'cancelled',  // rejected by admin or merchant
            ])->default('pending');

            $table->string('tx_hash', 100)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
