<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('balance_movements', function (Blueprint $table) {
      $table->string('idempotency_key', 120)->nullable()->unique()->after('type');
    });

    Schema::table('withdrawals', function (Blueprint $table) {
      $table->boolean('funds_reserved')->default(false)->after('status');
    });

    if (Schema::getConnection()->getDriverName() === 'mysql') {
      DB::statement("ALTER TABLE balance_movements MODIFY COLUMN type ENUM('credit','debit','fee','refund','adjustment','hold') NOT NULL");
    }

    $defaultPermissions = json_encode([
      'currencies.read',
      'invoices.create',
      'invoices.read',
      'invoices.cancel',
    ]);

    DB::table('api_keys')
      ->where(function ($query) {
        $query->whereNull('permissions')
          ->orWhere('permissions', '[]')
          ->orWhere('permissions', 'null');
      })
      ->update(['permissions' => $defaultPermissions]);
  }

  public function down(): void
  {
    Schema::table('withdrawals', function (Blueprint $table) {
      $table->dropColumn('funds_reserved');
    });

    Schema::table('balance_movements', function (Blueprint $table) {
      $table->dropColumn('idempotency_key');
    });
  }
};
