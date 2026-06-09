<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name');                        // label: "Production", "Test"
            // Only the SHA-256 hash is stored; raw key shown once at creation
            $table->string('key_hash', 64)->unique();
            $table->string('key_prefix', 12);              // first 12 chars for display: "cryn_abc…"
            $table->json('permissions')->nullable();        // ['invoices.create','invoices.read',...]
            $table->json('ip_whitelist')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['key_hash', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
