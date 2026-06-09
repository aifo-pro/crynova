<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Team access: an account owner can grant other users access to sections of
 * their account with a role and a set of allowed sections.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('role', 20)->default('viewer');   // viewer | manager | admin
            $table->json('sections')->nullable();             // allowed cabinet sections
            $table->string('status', 20)->default('invited'); // invited | active
            $table->timestamps();

            $table->unique(['owner_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
