<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each row = one on-chain address assigned to a payment invoice.
        // Hot wallet: derived from HD master key stored encrypted in settings.
        // Cold wallet: manual sweep addresses; no private key stored here.
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained();

            // invoice_id added later via separate migration after payment_invoices exists
            $table->unsignedBigInteger('invoice_id')->nullable();

            $table->string('address', 100);
            $table->string('memo', 100)->nullable();       // XRP tag, Tron memo, etc.

            // HD derivation path — private key is NEVER stored; re-derived on demand
            $table->string('hd_path', 50)->nullable();     // e.g. m/44'/0'/0'/0/42

            // hot = platform controls key; cold = manual custody; external = merchant's own
            $table->enum('type', ['hot', 'cold', 'external'])->default('hot');

            $table->decimal('balance', 36, 18)->default(0);
            $table->decimal('balance_unconfirmed', 36, 18)->default(0);

            $table->boolean('is_used')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['currency_id', 'address']);
            $table->index(['address', 'currency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
