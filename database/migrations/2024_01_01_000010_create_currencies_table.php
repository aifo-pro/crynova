<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();          // BTC, ETH, USDT_TRC20, etc.
            $table->string('name', 100);
            $table->string('network', 50);                 // bitcoin, ethereum, tron, bsc, litecoin, dogecoin
            $table->string('contract_address', 100)->nullable(); // ERC20/TRC20/BEP20 contracts
            $table->unsignedTinyInteger('decimals')->default(8);
            $table->unsignedTinyInteger('confirmations_required')->default(3);
            $table->decimal('min_amount', 36, 18)->default(0);
            $table->decimal('max_amount', 36, 18)->nullable();
            // Network fee estimate in native coin (updated by scheduler)
            $table->decimal('estimated_fee', 36, 18)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('supports_memo')->default(false); // XRP, XLM style
            $table->json('node_config')->nullable();        // extra per-currency node params (encrypted)
            $table->timestamps();

            $table->index(['is_active', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
