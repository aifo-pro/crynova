<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Trybit-style project fields collected during the create wizard:
 *   - accept_type: where payments are taken (own website vs hosted donation page)
 *   - integration URLs (success / fail / callback) and optional CMS hint
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('accept_type', 20)->default('website')->after('merchant_type'); // website | donation
            $table->string('cms', 50)->nullable()->after('website');
            $table->string('success_url')->nullable()->after('cms');
            $table->string('fail_url')->nullable()->after('success_url');
            $table->string('callback_url')->nullable()->after('fail_url');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['accept_type', 'cms', 'success_url', 'fail_url', 'callback_url']);
        });
    }
};
