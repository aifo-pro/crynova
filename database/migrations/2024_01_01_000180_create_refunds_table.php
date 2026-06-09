<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('payment_invoices')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 36, 18);                  // refund amount (may be partial)
            $table->string('to_address');                       // destination wallet
            $table->string('memo')->nullable();
            $table->enum('type', ['full', 'partial'])->default('full');
            // pending → approved → processing → completed | rejected | failed
            $table->enum('status', ['pending', 'approved', 'processing', 'completed', 'rejected', 'failed'])->default('pending');
            $table->string('tx_hash')->nullable();
            $table->text('reason')->nullable();                 // merchant-provided reason
            $table->text('admin_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
