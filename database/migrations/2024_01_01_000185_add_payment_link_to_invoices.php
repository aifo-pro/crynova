<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            $table->foreignId('payment_link_id')
                ->nullable()
                ->after('merchant_id')
                ->constrained('payment_links')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\PaymentLink::class);
            $table->dropColumn('payment_link_id');
        });
    }
};
