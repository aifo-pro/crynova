<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->decimal('max_invoice_amount', 18, 2)->nullable()->after('fee_percent');
            $table->decimal('daily_turnover_limit', 18, 2)->nullable()->after('max_invoice_amount');
            $table->decimal('monthly_turnover_limit', 18, 2)->nullable()->after('daily_turnover_limit');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['max_invoice_amount', 'daily_turnover_limit', 'monthly_turnover_limit']);
        });
    }
};
