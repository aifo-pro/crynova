<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Auto-conversion: automatically convert incoming crypto into a target
 * stablecoin/currency at settlement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->boolean('autoconvert_enabled')->default(false)->after('widget_config');
            $table->foreignId('autoconvert_target_currency_id')->nullable()->after('autoconvert_enabled')->constrained('currencies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('autoconvert_target_currency_id');
            $table->dropColumn('autoconvert_enabled');
        });
    }
};
