<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_type', 50)->default('user'); // user, system, api
            $table->string('actor_ip', 45)->nullable();
            $table->string('action', 100);                 // invoice.created, withdrawal.approved, etc.
            // Polymorphic subject: what was acted upon
            $table->nullableMorphs('subject');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'action', 'created_at']);
            // nullableMorphs() already creates subject_type + subject_id index
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
