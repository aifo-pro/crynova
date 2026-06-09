<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Trybit-style public project identifiers shown on the "My projects" cards:
 *   - shop_id: public, non-guessable project identifier (used in the hosted pay URL)
 *   - test_mode: per-project sandbox toggle
 *   - api_key_encrypted: the project's primary API key, stored encrypted so it can be
 *     displayed (masked) and copied later. Its SHA-256 hash also lives in api_keys for auth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('shop_id', 32)->nullable()->unique()->after('slug');
            $table->boolean('test_mode')->default(true)->after('is_active');
            $table->text('api_key_encrypted')->nullable()->after('webhook_secret');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['shop_id', 'test_mode', 'api_key_encrypted']);
        });
    }
};
