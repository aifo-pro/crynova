<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hashed recovery word. Set by the user before enabling 2FA; required
            // by an admin to reset 2FA when the user loses authenticator access.
            $table->string('tfa_recovery_word')->nullable()->after('google2fa_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tfa_recovery_word');
        });
    }
};
