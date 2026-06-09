<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();                // public-facing ID in URL & API
            $table->foreignId('merchant_id')->constrained();
            $table->foreignId('currency_id')->constrained();

            // Merchant's order reference
            $table->string('order_id')->nullable();
            $table->text('description')->nullable();

            // Amount in invoice currency (what merchant expects)
            $table->decimal('amount', 36, 18);

            // Amount actually received (filled by blockchain scanner)
            $table->decimal('amount_received', 36, 18)->default(0);

            // Payment address assigned to this invoice
            $table->string('pay_address', 100)->nullable();
            $table->string('pay_memo', 100)->nullable();   // for memo-based chains

            $table->enum('status', [
                'pending',               // created, waiting for user to send
                'waiting_confirmations', // tx seen in mempool / unconfirmed
                'paid',                  // confirmed, correct amount
                'underpaid',             // confirmed, but less than required
                'overpaid',              // confirmed, more than required
                'expired',               // TTL passed without payment
                'failed',                // internal error
                'refunded',              // merchant or admin issued refund
            ])->default('pending');

            // Exchange rate at time of invoice creation (for fiat display)
            $table->decimal('rate_usd', 24, 8)->nullable();

            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->decimal('fee_amount', 36, 18)->default(0);
            $table->decimal('net_amount', 36, 18)->default(0); // after fee

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Webhook retry state
            $table->unsignedTinyInteger('webhook_attempts')->default(0);
            $table->timestamp('webhook_last_sent_at')->nullable();
            $table->boolean('webhook_delivered')->default(false);

            // Extra data merchant passes (order metadata, customer info)
            $table->json('metadata')->nullable();

            // Refund info
            $table->string('refund_address')->nullable();
            $table->string('refund_tx_hash', 100)->nullable();

            $table->timestamps();

            $table->index(['merchant_id', 'status']);
            $table->index(['pay_address', 'currency_id']);
            $table->index(['status', 'expires_at']);
            $table->index(['order_id', 'merchant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_invoices');
    }
};
