<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_modules', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->string('name_pl')->nullable()->after('name_en');
            $table->string('description_en')->nullable()->after('description');
            $table->string('description_pl')->nullable()->after('description_en');
            $table->text('long_description_en')->nullable()->after('long_description');
            $table->text('long_description_pl')->nullable()->after('long_description_en');
        });
    }

    public function down(): void
    {
        Schema::table('integration_modules', function (Blueprint $table) {
            $table->dropColumn([
                'name_en', 'name_pl',
                'description_en', 'description_pl',
                'long_description_en', 'long_description_pl',
            ]);
        });
    }
};
