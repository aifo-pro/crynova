<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('slug');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
            $table->string('meta_title_en')->nullable()->after('meta_description');
            $table->string('meta_title_pl')->nullable()->after('meta_title_en');
            $table->string('meta_description_en', 500)->nullable()->after('meta_title_pl');
            $table->string('meta_description_pl', 500)->nullable()->after('meta_description_en');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title', 'meta_description',
                'meta_title_en', 'meta_title_pl',
                'meta_description_en', 'meta_description_pl',
            ]);
        });
    }
};
