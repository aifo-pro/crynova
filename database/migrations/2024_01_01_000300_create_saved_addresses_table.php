<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reusable payout addresses saved by a user (address book)
        Schema::create('saved_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('address');
            $table->string('memo')->nullable();
            $table->timestamps();
        });

        // Threshold-based automatic withdrawal rules per merchant + currency
        Schema::create('auto_withdraw_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->string('address');
            $table->string('memo')->nullable();
            $table->decimal('min_amount', 36, 18)->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['merchant_id', 'currency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_withdraw_rules');
        Schema::dropIfExists('saved_addresses');
    }
};
