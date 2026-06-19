<?php

use App\Support\ModuleCatalog;
use Illuminate\Database\Migrations\Migration;

/**
 * Re-syncs the module catalog to add the Flute CMS module. Idempotent —
 * ModuleCatalog::sync() rebuilds every archive and upserts every row.
 */
return new class extends Migration
{
    public function up(): void
    {
        ModuleCatalog::sync();
    }

    public function down(): void
    {
        // Catalog removal is handled by the original seed migration's down().
    }
};
