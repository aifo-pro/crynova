<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('token', 32)->unique();              // public-facing URL token
            $table->string('title')->nullable();                // merchant's label
            $table->text('description')->nullable();
            // amount=null means customer enters amount themselves
            $table->decimal('amount', 36, 18)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_id_prefix')->nullable();      // e.g. "ORDER-" → "ORDER-20250601"
            $table->string('success_url')->nullable();          // redirect after payment
            $table->integer('max_uses')->nullable();            // null = unlimited
            $table->integer('use_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_links');
    }
};
