<?php

use App\Support\ModuleCatalog;
use Illuminate\Database\Migrations\Migration;

/**
 * Seeds the official CMS integration modules into the catalog (builds ZIPs,
 * publishes covers, upserts rows). Logic lives in App\Support\ModuleCatalog so
 * it stays idempotent and reusable.
 */
return new class extends Migration
{
    public function up(): void
    {
        ModuleCatalog::sync();
    }

    public function down(): void
    {
        ModuleCatalog::removeAll();
    }
};
