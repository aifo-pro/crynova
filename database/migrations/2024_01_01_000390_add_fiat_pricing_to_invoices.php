<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Fiat-priced invoices: an invoice can be priced in a fiat currency (UAH, USD…)
 * while the crypto used for payment is chosen by the customer at checkout.
 *   - price_amount / price_currency = the original (fiat or crypto) price.
 *   - currency_id / amount / pay_address stay NULL until the customer picks a
 *     crypto, then are filled with the converted amount + deposit address.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            $table->decimal('price_amount', 36, 18)->nullable()->after('description');
            $table->string('price_currency', 16)->nullable()->after('price_amount');
        });

        // Allow these to be null until the customer selects a crypto.
        Schema::table('payment_invoices', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->change();
            $table->decimal('amount', 36, 18)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            $table->dropColumn(['price_amount', 'price_currency']);
        });
    }
};
