<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120)->default('mobile-app');
            $table->string('token', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });

        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32); // match | team | competition
            $table->string('sport', 32);
            $table->string('entity_id', 120);
            $table->string('name', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type', 'sport', 'entity_id'], 'user_favorites_unique');
            $table->index(['user_id', 'type']);
        });

        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_token', 512)->unique();
            $table->string('platform', 20)->default('android'); // android | ios | web
            $table->string('device_name', 120)->nullable();
            $table->boolean('push_enabled')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_device_tokens');
        Schema::dropIfExists('user_favorites');
        Schema::dropIfExists('api_tokens');
    }
};
