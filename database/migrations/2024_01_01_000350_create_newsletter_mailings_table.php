<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_mailings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->longText('body');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_mailings');
    }
};
