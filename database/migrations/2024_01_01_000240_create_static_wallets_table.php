<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Static (permanent) deposit wallets a merchant can assign to a specific client.
 * Unlike per-invoice addresses, these persist and aggregate all deposits for a
 * given client identifier. Private keys are NEVER stored — only public address + HD path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->string('address');
            $table->string('memo')->nullable();
            $table->string('client_identifier')->nullable();   // merchant's own client id/label
            $table->string('hd_path')->nullable();              // derivation path (no private key)
            $table->string('status', 10)->default('active');    // active | disabled
            $table->timestamps();

            $table->index(['merchant_id', 'currency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_wallets');
    }
};
