<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Currencies a merchant has enabled for their project (many-to-many).
 * Merchants pick currencies on creation and can toggle later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_merchant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['merchant_id', 'currency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_merchant');
    }
};
