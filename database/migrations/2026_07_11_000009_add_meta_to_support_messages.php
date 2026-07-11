<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('support_messages', 'meta')) {
                $table->json('meta')->nullable()->after('body');
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            if (Schema::hasColumn('support_messages', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }
};
