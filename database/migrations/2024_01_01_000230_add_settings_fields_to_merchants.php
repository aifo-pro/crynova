<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Project settings (Trybit "Комиссии" + "Интеграция" tabs):
 *   - who pays the transfer (network) fee and the service fee
 *   - partial-payment auto-confirm threshold (fixed amount or percent)
 *   - AML screening toggle
 *   - POSTBACK (callback) body format
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('transfer_fee_payer', 10)->default('client')->after('fee_percent');   // client | merchant
            $table->string('service_fee_payer', 10)->default('merchant')->after('transfer_fee_payer');
            $table->decimal('partial_confirm_value', 18, 6)->default(0)->after('service_fee_payer');
            $table->string('partial_confirm_unit', 10)->default('fixed')->after('partial_confirm_value'); // fixed | percent
            $table->boolean('aml_enabled')->default(false)->after('partial_confirm_unit');
            $table->string('postback_format', 10)->default('json')->after('callback_url'); // json | form-data
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'transfer_fee_payer', 'service_fee_payer',
                'partial_confirm_value', 'partial_confirm_unit',
                'aml_enabled', 'postback_format',
            ]);
        });
    }
};
