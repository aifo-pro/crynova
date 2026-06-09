<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Current balance snapshot per merchant per currency
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained();
            $table->decimal('available', 36, 18)->default(0);
            $table->decimal('locked', 36, 18)->default(0);  // funds pending withdrawal
            $table->timestamps();

            $table->unique(['merchant_id', 'currency_id']);
        });

        // Immutable ledger: every credit/debit appended here
        Schema::create('balance_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained();
            // invoice or withdrawal that caused this movement
            $table->morphs('movable');
            $table->enum('type', ['credit', 'debit', 'fee', 'refund', 'adjustment']);
            $table->decimal('amount', 36, 18);
            $table->decimal('balance_before', 36, 18);
            $table->decimal('balance_after', 36, 18);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'currency_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_movements');
        Schema::dropIfExists('balances');
    }
};
