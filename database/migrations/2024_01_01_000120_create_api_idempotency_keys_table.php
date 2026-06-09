<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key', 128);
            $table->string('request_hash', 64);
            $table->unsignedSmallInteger('http_status');
            $table->json('response_body');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['merchant_id', 'idempotency_key']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_idempotency_keys');
    }
};
