<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_unsubscribes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('token', 80)->unique();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('source', 50)->nullable();
            $table->timestamps();

            $table->index('unsubscribed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_unsubscribes');
    }
};
