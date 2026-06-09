<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Restructure merchants to the AIFO-style lifecycle:
 *   - A user can own multiple merchants (stores).
 *   - Each merchant goes through: unverified → moderation → active
 *     (with rejected / blocked as terminal/admin states).
 *   - Verification proves ownership of a domain or Telegram channel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // Lifecycle status (replaces the simple is_active boolean as source of truth)
            $table->string('status', 20)->default('unverified')->after('logo_path');

            // Merchant connection type
            $table->string('merchant_type', 20)->default('domain')->after('status'); // domain | telegram
            $table->string('domain')->nullable()->after('merchant_type');
            $table->string('telegram_channel')->nullable()->after('domain');

            // Business profile (collected at creation, used for moderation)
            $table->string('business_type')->nullable()->after('telegram_channel');
            $table->text('project_description')->nullable()->after('business_type');
            $table->string('base_currency_code', 10)->default('USD')->after('project_description');

            // Domain/Telegram ownership verification
            $table->string('verification_code', 64)->nullable()->after('base_currency_code');
            $table->string('verification_method', 20)->nullable()->after('verification_code'); // file | homepage | dns | telegram
            $table->timestamp('verified_at')->nullable()->after('verification_method');

            // Admin moderation
            $table->text('reject_reason')->nullable()->after('verified_at');
            $table->foreignId('moderated_by')->nullable()->after('reject_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn([
                'status', 'merchant_type', 'domain', 'telegram_channel',
                'business_type', 'project_description', 'base_currency_code',
                'verification_code', 'verification_method', 'verified_at',
                'reject_reason', 'moderated_at',
            ]);
        });
    }
};
