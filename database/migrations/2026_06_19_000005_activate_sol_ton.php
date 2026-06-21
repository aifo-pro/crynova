<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Activates native SOL and TON. They were seeded inactive because they require a
 * shared deposit address to be configured first; that is now done, so they can
 * appear in project currencies and checkout.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('currencies')->whereIn('code', ['SOL', 'TON'])->update(['is_active' => true]);
    }

    public function down(): void
    {
        DB::table('currencies')->whereIn('code', ['SOL', 'TON'])->update(['is_active' => false]);
    }
};
