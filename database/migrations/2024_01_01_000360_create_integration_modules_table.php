<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Admin-managed integration modules (CMS plugins) offered for download in the
 * user cabinet. No merchant data — purely a curated download catalog.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->string('icon')->default('layout');
            $table->string('version')->nullable();
            $table->string('file_path')->nullable();      // uploaded archive (public disk)
            $table->string('external_url')->nullable();    // or an external download link
            $table->boolean('is_active')->default(true);   // visible + downloadable in cabinet
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_modules');
    }
};
