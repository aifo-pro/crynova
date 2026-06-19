<?php

use App\Support\ModuleCatalog;
use Illuminate\Database\Migrations\Migration;

/**
 * Re-syncs the catalog after the translation columns are added (000005), so the
 * official modules get their EN/PL name, description and long description.
 */
return new class extends Migration
{
    public function up(): void
    {
        ModuleCatalog::sync();
    }

    public function down(): void
    {
        // Translations are dropped with the columns in 000005's down().
    }
};
