<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Persisted configs for the checkout-page builder and the payment widget builder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->json('checkout_config')->nullable()->after('postback_format');
            $table->json('widget_config')->nullable()->after('checkout_config');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['checkout_config', 'widget_config']);
        });
    }
};
