<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_departments')) {
            Schema::create('support_departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('support_department_user')) {
            Schema::create('support_department_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('support_department_id')->constrained('support_departments')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['support_department_id', 'user_id']);
            });
        }

        Schema::table('support_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('support_tickets', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('assigned_to')
                    ->constrained('support_departments')->nullOnDelete();
            }
        });

        // Seed default departments once.
        if (DB::table('support_departments')->count() === 0) {
            $now = now();
            $depts = [
                ['Загальні питання', 'general', 'Первинні звернення та загальна допомога'],
                ['Платежі', 'payments', 'Статуси рахунків, підтвердження, недоплати/переплати'],
                ['Технічні / Інтеграція', 'technical', 'API, вебхуки, модулі, налаштування'],
                ['Верифікація / KYC', 'kyc', 'Верифікація особи та документів'],
                ['Фінанси / Виплати', 'finance', 'Виведення коштів, повернення, комісії'],
            ];
            foreach ($depts as $i => [$name, $slug, $desc]) {
                DB::table('support_departments')->insert([
                    'name' => $name, 'slug' => $slug, 'description' => $desc,
                    'is_active' => true, 'sort' => $i, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('support_tickets', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }
        });
        Schema::dropIfExists('support_department_user');
        Schema::dropIfExists('support_departments');
    }
};
