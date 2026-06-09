<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('general');
            $table->string('key')->unique();
            // Values are stored encrypted for sensitive keys (HD seed, node passwords)
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->string('type', 20)->default('string'); // string|int|bool|json
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
