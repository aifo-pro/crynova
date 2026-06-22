<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Russian (_ru) translation columns for CMS content, mirroring _en/_pl so the
 * tr() accessor resolves Russian and admin RU tabs can store it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('title_ru')->nullable()->after('title_pl');
            $table->longText('body_ru')->nullable()->after('body_pl');
            $table->string('meta_title_ru')->nullable()->after('meta_title_pl');
            $table->text('meta_description_ru')->nullable()->after('meta_description_pl');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('title_ru')->nullable()->after('title_pl');
            $table->string('excerpt_ru', 1000)->nullable()->after('excerpt_pl');
            $table->longText('body_ru')->nullable()->after('body_pl');
            $table->string('meta_title_ru')->nullable()->after('meta_title_pl');
            $table->string('meta_description_ru', 500)->nullable()->after('meta_description_pl');
        });

        Schema::table('integration_modules', function (Blueprint $table) {
            $table->string('name_ru')->nullable()->after('name_pl');
            $table->string('description_ru')->nullable()->after('description_pl');
            $table->text('long_description_ru')->nullable()->after('long_description_pl');
        });
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $t) => $t->dropColumn(['title_ru', 'body_ru', 'meta_title_ru', 'meta_description_ru']));
        Schema::table('blog_posts', fn (Blueprint $t) => $t->dropColumn(['title_ru', 'excerpt_ru', 'body_ru', 'meta_title_ru', 'meta_description_ru']));
        Schema::table('integration_modules', fn (Blueprint $t) => $t->dropColumn(['name_ru', 'description_ru', 'long_description_ru']));
    }
};
