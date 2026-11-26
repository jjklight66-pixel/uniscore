<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SportsMatch;
use App\Services\UniScoreApiService;
use Illuminate\Support\Facades\File;

class SyncSportsData extends Command
{
    protected $signature = 'sports:sync {--source=all}';
    protected $description = 'Sync sports matches from nguon2-uniscore and nguon1-sofascore';

    public function handle(): int
    {
        $this->info("Starting Sports Data Synchronization...");
        $syncedCount = 0;

        // 1. Sync from nguon2-uniscore
        $uniOutputDir = base_path('../nguon2-uniscore/output');
        if (File::exists($uniOutputDir)) {
            $jsonFiles = File::allFiles($uniOutputDir);
            foreach ($jsonFiles as $file) {
                if ($file->getExtension() !== 'json') continue;

                $sportKey = basename(dirname($file->getRealPath()));
                $dateStr = pathinfo($file->getFilename(), PATHINFO_FILENAME);

                $content = json_decode(File::get($file->getRealPath()), true);
                $matches = $content['matches'] ?? [];

                foreach ($matches as $m) {
                    $tax = $m['taxonomy'] ?? [];
                    $tour = $tax['tournament_league'] ?? [];
                    $ent = $m['entities'] ?? [];
                    $teams = $ent['teams_or_players'] ?? [];
                    $home = $teams['home'] ?? [];
                    $away = $teams['away'] ?? [];
                    $timeline = $m['match_timeline'] ?? [];
                    $scores = $timeline['scores'] ?? [];

                    SportsMatch::updateOrCreate(
                        [
                            'external_id' => $m['id'],
                            'source' => 'uniscore',
                        ],
                        [
                            'sport_key' => $sportKey,
                            'tournament_name' => $tour['name'] ?? 'Unknown Tournament',
                            'tournament_country' => $tax['country_region']['name'] ?? 'International',
                            'country_code' => $tax['country_region']['iso_code'] ?? null,
                            'home_team' => $home['name'] ?? 'Home',
                            'away_team' => $away['name'] ?? 'Away',
                            'home_logo' => !empty($home['id']) ? UniScoreApiService::teamLogo($sportKey, $home['id']) : ($home['logo_url'] ?? null),
                            'away_logo' => !empty($away['id']) ? UniScoreApiService::teamLogo($sportKey, $away['id']) : ($away['logo_url'] ?? null),
                            'home_score' => $scores['home_total'] ?? null,
                            'away_score' => $scores['away_total'] ?? null,
                            'state_category' => $timeline['state_category'] ?? 'Upcoming',
                            'status_description' => $timeline['status_description'] ?? 'not_started',
                            'match_date' => $dateStr,
                            'start_time_iso' => $timeline['start_time_iso'] ?? null,
                            'readtime' => $timeline['readtime']['minute'] ?? null,
                            'payload' => $m,
                        ]
                    );
                    $syncedCount++;
                }
            }
            $this->info("Successfully synced {$syncedCount} matches from nguon2-uniscore!");
        }

        $this->info("Total matches in database: " . SportsMatch::count());
        return Command::SUCCESS;
    }
}
