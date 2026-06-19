<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Owner of a static (reusable) deposit wallet. Null for pool wallets.
            $table->foreignId('merchant_id')->nullable()->after('currency_id')->constrained()->nullOnDelete();
            // One static wallet per (merchant, currency). Pool wallets have a null
            // merchant_id, which MySQL treats as distinct, so they never collide.
            $table->unique(['merchant_id', 'currency_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merchant_id');
        });
    }
};
