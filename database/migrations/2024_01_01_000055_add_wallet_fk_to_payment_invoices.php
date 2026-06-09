<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds the FK from wallets.invoice_id -> payment_invoices.id
// Split out because wallets must exist before payment_invoices (address assignment)
// and payment_invoices also references wallets via pay_address (no FK, just string).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->foreign('invoice_id')
                  ->references('id')
                  ->on('payment_invoices')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });
    }
};
