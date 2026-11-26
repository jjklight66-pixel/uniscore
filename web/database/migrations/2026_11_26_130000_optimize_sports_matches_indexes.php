<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sports_matches', function (Blueprint $table) {
            if (!Schema::hasColumn('sports_matches', 'tournament_id')) {
                $table->string('tournament_id', 64)->nullable()->after('tournament_name');
            }
            $table->index(['sport_key', 'match_date'], 'sports_matches_sport_date_idx');
            $table->index(['sport_key', 'state_category'], 'sports_matches_sport_state_idx');
            $table->index(['sport_key', 'tournament_id'], 'sports_matches_sport_tour_idx');
        });

        // Backfill tournament_id from payload JSON for all existing matches
        DB::table('sports_matches')
            ->select('id', 'payload')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $payload = is_string($row->payload) ? json_decode($row->payload, true) : null;
                    $tourId = $payload['taxonomy']['tournament_league']['id'] ?? null;
                    if ($tourId) {
                        DB::table('sports_matches')
                            ->where('id', $row->id)
                            ->update(['tournament_id' => (string) $tourId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('sports_matches', function (Blueprint $table) {
            $table->dropIndex('sports_matches_sport_date_idx');
            $table->dropIndex('sports_matches_sport_state_idx');
            $table->dropIndex('sports_matches_sport_tour_idx');
            if (Schema::hasColumn('sports_matches', 'tournament_id')) {
                $table->dropColumn('tournament_id');
            }
        });
    }
};
