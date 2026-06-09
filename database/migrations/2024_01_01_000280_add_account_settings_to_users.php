<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Account settings fields: contact (Telegram), UI language, notification
 * preferences, and an account-level API key (for account-wide actions).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram')->nullable()->after('email');
            $table->string('language', 5)->default('ru')->after('telegram');
            $table->json('notification_prefs')->nullable()->after('language');
            $table->text('account_api_key_encrypted')->nullable()->after('notification_prefs');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram', 'language', 'notification_prefs', 'account_api_key_encrypted']);
        });
    }
};
