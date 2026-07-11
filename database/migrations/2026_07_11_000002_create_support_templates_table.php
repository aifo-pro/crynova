<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_templates')) {
            return;
        }

        Schema::create('support_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->nullable();
            $table->text('body');            // uk (default)
            $table->text('body_en')->nullable();
            $table->text('body_pl')->nullable();
            $table->text('body_ru')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_templates');
    }
};
