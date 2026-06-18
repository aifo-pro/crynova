<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_modules', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('icon');     // module photo / cover
            $table->text('long_description')->nullable()->after('description'); // full description for the detail page
        });
    }

    public function down(): void
    {
        Schema::table('integration_modules', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'long_description']);
        });
    }
};
