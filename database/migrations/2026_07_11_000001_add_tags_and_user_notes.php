<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            if (! Schema::hasColumn('merchants', 'tags')) {
                $table->json('tags')->nullable()->after('admin_note');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'admin_note')) {
                $table->text('admin_note')->nullable();
            }
            if (! Schema::hasColumn('users', 'tags')) {
                $table->json('tags')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            if (Schema::hasColumn('merchants', 'tags')) {
                $table->dropColumn('tags');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach (['admin_note', 'tags'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
