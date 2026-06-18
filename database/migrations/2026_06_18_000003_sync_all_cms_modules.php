<?php

use App\Support\ModuleCatalog;
use Illuminate\Database\Migrations\Migration;

/**
 * Re-syncs the full set of CMS modules. Needed for environments where the
 * earlier 000002 migration already ran with only the first two modules — this
 * fresh migration runs ModuleCatalog::sync() again to add the rest. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        ModuleCatalog::sync();
    }

    public function down(): void
    {
        // Keep the catalog; removal is handled by 000002's down().
    }
};
