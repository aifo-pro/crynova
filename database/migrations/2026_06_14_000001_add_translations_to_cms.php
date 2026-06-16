<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->string('title_pl')->nullable()->after('title_en');
            $table->longText('body_en')->nullable()->after('body');
            $table->longText('body_pl')->nullable()->after('body_en');
            $table->string('meta_title_en')->nullable()->after('meta_title');
            $table->string('meta_title_pl')->nullable()->after('meta_title_en');
            $table->text('meta_description_en')->nullable()->after('meta_description');
            $table->text('meta_description_pl')->nullable()->after('meta_description_en');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->string('title_pl')->nullable()->after('title_en');
            $table->string('excerpt_en', 1000)->nullable()->after('excerpt');
            $table->string('excerpt_pl', 1000)->nullable()->after('excerpt_en');
            $table->longText('body_en')->nullable()->after('body');
            $table->longText('body_pl')->nullable()->after('body_en');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['title_en','title_pl','body_en','body_pl','meta_title_en','meta_title_pl','meta_description_en','meta_description_pl']);
        });
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['title_en','title_pl','excerpt_en','excerpt_pl','body_en','body_pl']);
        });
    }
};
