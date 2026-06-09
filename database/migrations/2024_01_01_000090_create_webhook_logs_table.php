<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('payment_invoices')->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained();
            $table->string('event');                       // invoice.paid, invoice.expired, etc.
            $table->string('url');
            $table->json('payload');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('response_body')->nullable();
            $table->boolean('success')->default(false);
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'success']);
            $table->index(['success', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
