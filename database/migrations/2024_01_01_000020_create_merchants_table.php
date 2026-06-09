<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();

            // Fee settings: platform takes a % of each paid invoice
            $table->decimal('fee_percent', 5, 2)->default(1.00);

            // Merchant's payout wallet addresses per currency (plain — not private keys)
            $table->json('payout_addresses')->nullable();

            // Webhook
            $table->string('webhook_url')->nullable();
            $table->text('webhook_secret')->nullable(); // encrypted at rest (HMAC signing key)

            // KYC/AML placeholder (architecture ready, integration deferred)
            $table->enum('kyc_status', ['none', 'pending', 'approved', 'rejected'])->default('none');
            $table->json('kyc_data')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['slug', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
