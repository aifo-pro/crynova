<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blockchain_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained('payment_invoices')->nullOnDelete();
            $table->foreignId('currency_id')->constrained();

            $table->string('tx_hash', 100);
            $table->string('from_address', 100)->nullable();
            $table->string('to_address', 100);
            $table->decimal('amount', 36, 18);
            $table->decimal('fee', 36, 18)->default(0);    // network fee paid by sender
            $table->unsignedInteger('confirmations')->default(0);
            $table->unsignedInteger('confirmations_required')->default(1);

            $table->enum('direction', ['incoming', 'outgoing'])->default('incoming');
            $table->enum('status', ['pending', 'confirming', 'confirmed', 'failed'])->default('pending');

            $table->unsignedBigInteger('block_number')->nullable();
            $table->string('block_hash', 100)->nullable();
            $table->timestamp('block_time')->nullable();

            // Raw data from node — for audit / debugging (trimmed to avoid huge rows)
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->unique(['tx_hash', 'currency_id']);
            $table->index(['to_address', 'currency_id', 'status']);
            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockchain_transactions');
    }
};
