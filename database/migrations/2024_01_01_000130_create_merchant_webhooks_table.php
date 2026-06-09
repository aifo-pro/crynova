<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            // Raw signing secret shown once; stored hashed in webhooks_logs signing
            // For HMAC generation we store it encrypted
            $table->text('secret_encrypted')->nullable();
            $table->json('events')->nullable();       // ['invoice.paid', 'invoice.expired', ...]
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_webhooks');
    }
};
