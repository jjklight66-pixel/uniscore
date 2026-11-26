<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sports_matches', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->index();
            $table->string('source')->default('uniscore')->index(); // 'uniscore' or 'sofascore'
            $table->string('sport_key')->default('football')->index();
            $table->string('tournament_name')->index();
            $table->string('tournament_country')->nullable();
            $table->string('country_code', 10)->nullable();
            $table->string('home_team');
            $table->string('away_team');
            $table->string('home_logo')->nullable();
            $table->string('away_logo')->nullable();
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->string('state_category')->default('Upcoming')->index();
            $table->string('status_description')->nullable();
            $table->string('match_date', 16)->index(); // YYYY-MM-DD
            $table->string('start_time_iso')->nullable();
            $table->string('readtime')->nullable();
            $table->boolean('is_pinned')->default(false)->index();
            $table->integer('priority_order')->default(0)->index();
            $table->json('payload')->nullable(); // Target schema 4-tier tree
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports_matches');
    }
};
