<?php

namespace App\Services;

use App\Models\SportsMatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UniScoreApiService
{
    private const BASE_URL = 'https://api.uni-score.com/api/v2';

    public const SPORT_SLUGS = [
        'football' => 'football',
        'basketball' => 'basketball',
        'tennis' => 'tennis',
        'american_football' => 'am-football',
        'baseball' => 'baseball',
        'badminton' => 'badminton',
        'volleyball' => 'volleyball',
        'cricket' => 'cricket',
        'ice_hockey' => 'hockey',
        'table_tennis' => 'table-tennis',
    ];

    public const IMG_SLUGS = [
        'football' => 'football',
        'basketball' => 'basketball',
        'tennis' => 'tennis',
        'american_football' => 'american-football',
        'baseball' => 'baseball',
        'badminton' => 'badminton',
        'volleyball' => 'volleyball',
        'cricket' => 'cricket',
        'ice_hockey' => 'hockey',
        'table_tennis' => 'table-tennis',
    ];

    public static function encodeImageId(?string $id): string
    {
        if (!$id) return '';
        $shifts = [3, 1, 5, 9, 6, 2, 4, 7, 8, 0];
        $out = '';
        $len = strlen($id);
        for ($i = 0; $i < $len; $i++) {
            $ch = $id[$i];
            $shift = $shifts[$i % 10];
            $ord = ord($ch);
            if ($ord >= 48 && $ord <= 57) {
                $min = 48; $max = 57;
            } elseif ($ord >= 97 && $ord <= 122) {
                $min = 97; $max = 122;
            } else {
                $out .= $ch;
                continue;
            }
            $code = $ord - $shift;
            $out .= ($code >= $min) ? chr($code) : chr($max - abs($min - $code) + 1);
        }
        return $out;
    }

    public static function teamLogo(string $sportKey, ?string $teamId): string
    {
        if (!$teamId) return '';
        $s = self::IMG_SLUGS[$sportKey] ?? 'football';
        return "https://img.uniscore.com/{$s}/team/" . self::encodeImageId($teamId) . "/image/small/dark";
    }

    public static function tournamentLogo(string $sportKey, ?string $compId): string
    {
        if (!$compId) return '';
        $s = self::IMG_SLUGS[$sportKey] ?? 'football';
        return "https://img.uniscore.com/{$s}/competition/" . self::encodeImageId($compId) . "/image/small/dark";
    }

    public static function countryFlag(string $sportKey, ?string $countryId): string
    {
        if (!$countryId || $countryId === 'international') {
            return 'https://img.uniscore.com/football/competition/international/image/small/dark';
        }
        $s = self::IMG_SLUGS[$sportKey] ?? 'football';
        return "https://img.uniscore.com/{$s}/country/" . self::encodeImageId($countryId) . "/image/small/dark";
    }

    public static function playerAvatar(string $sportKey, ?string $playerId): string
    {
        if (!$playerId) return '';
        $s = self::IMG_SLUGS[$sportKey] ?? 'football';
        return "https://img.uniscore.com/{$s}/player/" . self::encodeImageId($playerId) . "/image/small/dark";
    }

    private function get(string $endpoint, array $params = []): ?array
    {
        try {
            $resp = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Origin' => 'https://uniscore.com',
                    'Referer' => 'https://uniscore.com/',
                ])
                ->get(self::BASE_URL . '/' . ltrim($endpoint, '/'), array_merge(['language' => 'vi'], $params));

            if ($resp->successful()) {
                return $resp->json();
            }
        } catch (\Throwable $e) {
            // Silently handle network failures
        }
        return null;
    }

    /**
     * Format Basketball live quarter + countdown clock (e.g. "Q2 08:40'").
     */
    public static function formatBasketballLiveClock(int $statusCode, int $remainSec): string
    {
        $period = match ($statusCode) {
            2 => 'Q1',
            3 => 'Nghỉ Q1',
            4 => 'Q2',
            5 => 'HT',
            6 => 'Q3',
            7 => 'Nghỉ Q3',
            8 => 'Q4',
            9 => 'OT',
            default => 'LIVE',
        };
        if (in_array($statusCode, [3, 5, 7])) return $period;
        if ($remainSec > 0) {
            $m = str_pad((string)intdiv($remainSec, 60), 2, '0', STR_PAD_LEFT);
            $s = str_pad((string)($remainSec % 60), 2, '0', STR_PAD_LEFT);
            return "{$period} {$m}:{$s}'";
        }
        return $period;
    }

    /**
     * Exact UniScore football live minute calculator (decoded from UniScore frontend).
     */
    public static function formatFootballLiveMinute(int $statusCode, int $periodStartTs): string
    {
        if ($statusCode === 6) return 'HT';
        if ($statusCode === 13) return 'PEN';
        if ($statusCode === 14) return 'ET';
        if ($statusCode === 15) return 'Tạm dừng';
        if ($periodStartTs <= 0) return 'LIVE';

        $elapsed = max(0, time() - $periodStartTs);
        $mins = intdiv($elapsed, 60);
        $secs = str_pad((string)($elapsed % 60), 2, '0', STR_PAD_LEFT);
        $m = $mins + 1;

        return match ($statusCode) {
            5 => $m > 45 ? "45+'" : "{$m}:{$secs}'",
            7 => ($m + 45) > 90 ? "90+'" : ($m + 45) . ":{$secs}'",
            33 => ($m + 90) > 105 ? "105+'" : ($m + 90) . ":{$secs}'",
            42 => ($m + 105) > 120 ? "120+'" : ($m + 105) . ":{$secs}'",
            default => 'LIVE',
        };
    }

    /**
     * =========================================================================
     * REAL-TIME LIVE SYNC: Always fetch ALL currently Live matches from UniScore
     * (12-second TTL), upsert them into SQLite, and clean up stale finished ones!
     * =========================================================================
     */
    public function syncLiveMatches(string $sportKey, bool $force = false): array
    {
        $slug = self::SPORT_SLUGS[$sportKey] ?? 'football';
        $cacheKey = "uniscore_live_sync_{$slug}";

        if ($force || !Cache::has($cacheKey)) {
            $liveIds = [];
            if ($sportKey === 'football') {
                $res = $this->get("sport/football/events/live-v2/locale/VN");
                $events = $res['data']['events'] ?? [];
                foreach ($events as $ev) {
                    if (!empty($ev['id'])) {
                        $liveIds[] = $ev['id'];
                        $this->upsertFootballEvent($ev, $sportKey, date('Y-m-d'), true);
                    }
                }
            } else {
                $res = $this->get("sport/{$slug}/events/live");
                $rawStr = $res['data']['events'] ?? '';
                if (is_string($rawStr) && $rawStr !== '') {
                    foreach (explode('!!', $rawStr) as $row) {
                        $parts = explode('^', $row);
                        if (!empty($parts[0])) {
                            $liveIds[] = $parts[0];
                            $this->upsertCompactEvent($row, $sportKey, date('Y-m-d'), true);
                        }
                    }
                }
            }

            // Mark stale DB rows that are no longer live as Finished (avoid stuck live matches)
            $staleQuery = SportsMatch::where('sport_key', $sportKey)
                ->where('state_category', 'like', '%Live%');
            if (!empty($liveIds)) {
                $staleQuery->whereNotIn('external_id', $liveIds);
            }
            $staleQuery->update(['state_category' => 'Finished', 'status_description' => 'ended']);

            Cache::put($cacheKey, true, 12);
        }

        return SportsMatch::where('sport_key', $sportKey)
            ->where('state_category', 'like', '%Live%')
            ->orderByDesc('is_pinned')
            ->orderBy('tournament_name')
            ->get()
            ->map(function ($m) {
                $rt = $m->payload['match_timeline']['readtime'] ?? [];
                return [
                    'id' => $m->id,
                    'external_id' => $m->external_id,
                    'home_team' => $m->home_team,
                    'away_team' => $m->away_team,
                    'home_score' => $m->home_score,
                    'away_score' => $m->away_score,
                    'state_category' => $m->state_category,
                    'status_code' => $m->payload['match_timeline']['status_code'] ?? 0,
                    'period_start_ts' => $rt['period_start_ts'] ?? 0,
                    'readtime' => $m->readtime,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * =========================================================================
     * CACHE-THROUGH PROXY: Auto-fetch matches from UniScore if missing in DB,
     * persist them straight into SQLite (`sports_matches`), and serve immediately!
     * =========================================================================
     */
    public function ensureDateMatchesInDb(string $sportKey, string $dateStr): int
    {
        $existingCount = SportsMatch::where('sport_key', $sportKey)
            ->where('match_date', $dateStr)
            ->count();

        $todayStr = date('Y-m-d');
        // Past dates that already exist in DB: serve 100% from SQLite, ZERO remote requests!
        if ($dateStr < $todayStr && $existingCount > 0) {
            return 0;
        }

        $cacheMarker = "uniscore_synced_{$sportKey}_{$dateStr}";
        // If we already have data in DB and recently checked this date, skip remote fetch
        if ($existingCount > 0 && Cache::has($cacheMarker)) {
            return 0;
        }

        $slug = self::SPORT_SLUGS[$sportKey] ?? 'football';
        $saved = 0;

        if ($sportKey === 'football') {
            $res = $this->get("sport/football/scheduled-events-pagination-v2/{$dateStr}/locale/VN/type/all", [
                'sortByMatchTime' => 'false',
                'page' => 1,
            ]);
            $events = $res['data']['events'] ?? [];
            foreach ($events as $ev) {
                if ($this->upsertFootballEvent($ev, $sportKey, $dateStr)) {
                    $saved++;
                }
            }
        } else {
            $res = $this->get("sport/{$slug}/scheduled-events/{$dateStr}/offset/-420");
            $rawStr = $res['data']['events'] ?? '';
            if (is_string($rawStr) && $rawStr !== '') {
                $chunks = explode('!!', $rawStr);
                // Limit to 80 matches per day on-demand so page stays fast
                foreach (array_slice($chunks, 0, 80) as $row) {
                    if ($this->upsertCompactEvent($row, $sportKey, $dateStr)) {
                        $saved++;
                    }
                }
            }
        }

        // Remember we checked this date (5 min TTL for today, 6 hours for other dates)
        $ttl = ($dateStr === date('Y-m-d')) ? 300 : 21600;
        Cache::put($cacheMarker, true, $ttl);
        return $saved;
    }

    /**
     * Upsert a structured JSON event (used for Football scheduled/live events & Competition fixtures).
     */
    public function upsertFootballEvent(array $ev, string $sportKey, ?string $fallbackDate = null, bool $forceLive = false): bool
    {
        $id = $ev['id'] ?? null;
        if (!$id) return false;

        $tour = $ev['tournament'] ?? [];
        $home = $ev['homeTeam'] ?? [];
        $away = $ev['awayTeam'] ?? [];
        $hScore = $ev['homeScore'] ?? [];
        $aScore = $ev['awayScore'] ?? [];
        $status = $ev['status'] ?? [];
        $code = (int) ($status['code'] ?? 0);
        $stType = strtolower((string) ($status['type'] ?? 'notstarted'));
        $stDesc = (string) ($status['description'] ?? 'not_started');

        $stateCat = 'Upcoming';
        if ($forceLive || $stType === 'inprogress' || in_array($code, [1, 2, 3, 4, 5, 6, 7, 8, 9, 13, 14, 33, 42])) {
            $stateCat = 'Live';
        } elseif ($stType === 'finished' || $code === 10 || $code === 100) {
            $stateCat = 'Finished';
        } elseif (in_array($stType, ['postponed', 'canceled', 'cancelled', 'interrupted', 'suspended'])) {
            $stateCat = 'Special (' . ucfirst($stType) . ')';
        }

        $ts = (int) ($ev['startTimestamp'] ?? 0);
        $dateStr = $ts > 0 ? date('Y-m-d', $ts) : ($fallbackDate ?? date('Y-m-d'));
        $iso = $ts > 0 ? gmdate('Y-m-d\TH:i:s\Z', $ts) : null;

        $periodStartTs = (int) ($ev['time']['currentPeriodStartTimestamp'] ?? 0);
        $readtimeLabel = ($stateCat === 'Live')
            ? self::formatFootballLiveMinute($code, $periodStartTs)
            : ($ev['time']['played'] ?? null);

        $homeLogo = self::teamLogo($sportKey, $home['id'] ?? null);
        $awayLogo = self::teamLogo($sportKey, $away['id'] ?? null);

        $payload = [
            'id' => $id,
            'slug' => $ev['slug'] ?? '',
            'taxonomy' => [
                'sport' => ['name' => SportsMatch::SPORTS[$sportKey]['name'] ?? $sportKey],
                'country_region' => [
                    'id' => $tour['category']['id'] ?? '',
                    'name' => $tour['category']['name'] ?? 'Quốc tế',
                ],
                'tournament_league' => [
                    'id' => $tour['id'] ?? '',
                    'name' => $tour['name'] ?? 'Unknown Tournament',
                    'slug' => $tour['slug'] ?? '',
                    'round' => $ev['roundInfo']['round'] ?? ($tour['stage']['name'] ?? ''),
                ],
            ],
            'entities' => [
                'teams_or_players' => [
                    'home' => ['id' => $home['id'] ?? '', 'name' => $home['name'] ?? 'Home', 'logo_url' => $homeLogo],
                    'away' => ['id' => $away['id'] ?? '', 'name' => $away['name'] ?? 'Away', 'logo_url' => $awayLogo],
                ],
                'venue' => $ev['venue'] ?? [],
                'match_officials' => !empty($ev['referee']['name']) ? [['name' => $ev['referee']['name'], 'role' => 'Main Referee']] : [],
                'squad_roster' => ['home' => [], 'away' => []],
            ],
            'match_timeline' => [
                'state_category' => $stateCat,
                'status_code' => $code,
                'status_description' => $stDesc,
                'start_timestamp' => $ts,
                'start_time_iso' => $iso,
                'readtime' => [
                    'minute' => $readtimeLabel,
                    'period_start_ts' => $periodStartTs,
                    'period_state' => $stDesc,
                ],
                'scores' => [
                    'home_total' => $hScore['display'] ?? ($hScore['current'] ?? null),
                    'away_total' => $aScore['display'] ?? ($aScore['current'] ?? null),
                    'period_breakdown' => ['home' => $hScore, 'away' => $aScore],
                ],
            ],
            'match_center' => [
                'live_events_summary' => [],
                'match_statistics' => [],
                'confirmed_lineups' => null,
            ],
        ];

        SportsMatch::updateOrCreate(
            ['external_id' => $id, 'source' => 'uniscore'],
            [
                'sport_key' => $sportKey,
                'tournament_name' => $tour['name'] ?? 'Unknown Tournament',
                'tournament_country' => $tour['category']['name'] ?? 'Quốc tế',
                'home_team' => $home['name'] ?? 'Home',
                'away_team' => $away['name'] ?? 'Away',
                'home_logo' => $homeLogo,
                'away_logo' => $awayLogo,
                'home_score' => $hScore['display'] ?? ($hScore['current'] ?? null),
                'away_score' => $aScore['display'] ?? ($aScore['current'] ?? null),
                'state_category' => $stateCat,
                'status_description' => $stDesc,
                'match_date' => $dateStr,
                'start_time_iso' => $iso,
                'readtime' => $readtimeLabel,
                'payload' => $payload,
            ]
        );
        return true;
    }

    /**
     * Exact per-sport compact `^`-delimited parser for ALL 9 non-football sports:
     * Basketball, Tennis, American Football, Baseball, Badminton, Volleyball, Cricket, Ice Hockey, Table Tennis.
     */
    private function upsertCompactEvent(string $raw, string $sportKey, string $dateStr, bool $forceLive = false): bool
    {
        $p = explode('^', $raw);
        if (count($p) < 16 || empty($p[0])) return false;

        $id = $p[0];
        $ts = is_numeric($p[1] ?? '') ? (int) $p[1] : 0;
        $tourId = $p[2] ?? '';

        $tourName = 'Unknown Tournament';
        $countryName = 'Quốc tế';
        $statusCode = 0;
        $statusDesc = '';
        $homeId = ''; $homeName = 'Home';
        $awayId = ''; $awayName = 'Away';
        $venueName = ''; $venueCity = '';
        $remainSec = 0;
        $hScore = null; $aScore = null;
        $periodBreakdown = [];
        $extraScores = null;
        $totalPoints = null;

        $parseJson = fn(int $idx) => (!empty($p[$idx]) && str_starts_with(trim($p[$idx]), '{')) ? (json_decode($p[$idx], true) ?: []) : [];
        $getInt = fn(int $idx) => (isset($p[$idx]) && is_numeric($p[$idx]) && (int)$p[$idx] >= 0) ? (int)$p[$idx] : null;

        switch ($sportKey) {
            case 'basketball':
                $tourName = $p[4] ?: ($p[7] ?? 'Basketball League');
                $countryName = $p[8] ?: ($p[6] ?? 'Quốc tế');
                $statusCode = is_numeric($p[9] ?? '') ? (int)$p[9] : 0;
                $homeId = $p[10] ?? ''; $homeName = $p[11] ?? 'Home';
                $awayId = $p[13] ?? ''; $awayName = $p[14] ?? 'Away';
                $remainSec = is_numeric($p[20] ?? '') ? (int)$p[20] : 0;
                $hScore = $getInt(21); $aScore = $getInt(28);
                $periodBreakdown = [
                    'home' => ['period1' => $getInt(22), 'period2' => $getInt(23), 'period3' => $getInt(24), 'period4' => $getInt(25), 'overtime' => $getInt(26)],
                    'away' => ['period1' => $getInt(29), 'period2' => $getInt(30), 'period3' => $getInt(31), 'period4' => $getInt(32), 'overtime' => $getInt(33)],
                ];
                break;

            case 'tennis':
                $tourName = $p[4] ?? 'Tennis Tournament';
                $countryName = $p[7] ?? 'Quốc tế';
                $statusCode = is_numeric($p[11] ?? '') ? (int)$p[11] : 0;
                $statusDesc = $p[12] ?? '';
                $homeId = $p[14] ?? ''; $homeName = $p[15] ?? 'Player 1';
                $awayId = $p[19] ?? ''; $awayName = $p[20] ?? 'Player 2';
                $venueName = $p[28] ?? ''; $venueCity = $p[30] ?? '';
                $sc = $parseJson(24);
                $hScore = isset($sc['ft'][0]) ? (int)$sc['ft'][0] : null;
                $aScore = isset($sc['ft'][1]) ? (int)$sc['ft'][1] : null;
                $periodBreakdown = $sc; // Contains p1..p5 and x1..x5 tiebreak scores!
                break;

            case 'american_football':
                $tourName = $p[3] ?? 'American Football';
                $countryName = $p[7] ?? 'USA';
                $statusCode = is_numeric($p[8] ?? '') ? (int)$p[8] : 0;
                $statusDesc = $p[10] ?? ($p[9] ?? '');
                $homeId = $p[11] ?? ''; $homeName = $p[12] ?? 'Home';
                $awayId = $p[18] ?? ''; $awayName = $p[19] ?? 'Away';
                $venueName = $p[29] ?? ''; $venueCity = $p[31] ?? '';
                $sc = $parseJson(25);
                $hScore = isset($sc['ft'][0]) ? (int)$sc['ft'][0] : null;
                $aScore = isset($sc['ft'][1]) ? (int)$sc['ft'][1] : null;
                $periodBreakdown = $sc;
                break;

            case 'baseball':
                $tourName = $p[7] ?? 'Baseball League';
                $countryName = 'Quốc tế';
                $statusCode = is_numeric($p[9] ?? '') ? (int)$p[9] : 0;
                $statusDesc = $p[10] ?? '';
                $homeId = $p[12] ?? ''; $homeName = $p[13] ?? 'Home';
                $awayId = $p[14] ?? ''; $awayName = $p[15] ?? 'Away';
                $sc = $parseJson(16);
                $hScore = (isset($sc['ft'][0]) && is_numeric($sc['ft'][0])) ? (int)$sc['ft'][0] : null;
                $aScore = (isset($sc['ft'][1]) && is_numeric($sc['ft'][1])) ? (int)$sc['ft'][1] : null;
                $periodBreakdown = $sc; // Contains p1..p9 + h (Hits) + e (Errors)!
                break;

            case 'badminton':
                $tourName = $p[4] ?? 'Badminton';
                $countryName = $p[7] ?? 'International';
                $statusCode = is_numeric($p[8] ?? '') ? (int)$p[8] : 0;
                $statusDesc = $p[9] ?? '';
                $homeId = $p[11] ?? ''; $homeName = $p[12] ?? 'Player 1';
                $awayId = $p[16] ?? ''; $awayName = $p[17] ?? 'Player 2';
                $sc = $parseJson(21);
                $hScore = isset($sc['ft'][0]) ? (int)$sc['ft'][0] : null;
                $aScore = isset($sc['ft'][1]) ? (int)$sc['ft'][1] : null;
                $periodBreakdown = $sc;
                break;

            case 'volleyball':
                $tourName = $p[3] ?? 'Volleyball';
                $countryName = $p[8] ?? 'Quốc tế';
                $statusCode = is_numeric($p[9] ?? '') ? (int)$p[9] : 0;
                $statusDesc = $p[10] ?? '';
                $homeId = $p[12] ?? ''; $homeName = $p[13] ?? 'Home';
                $awayId = $p[16] ?? ''; $awayName = $p[17] ?? 'Away';
                $sc = $parseJson(20);
                $hScore = isset($sc['ft'][0]) ? (int)$sc['ft'][0] : null;
                $aScore = isset($sc['ft'][1]) ? (int)$sc['ft'][1] : null;
                $periodBreakdown = $sc;
                break;

            case 'cricket':
                $tourName = $p[4] ?? 'Cricket';
                $countryName = $p[7] ?? 'International';
                $statusCode = is_numeric($p[8] ?? '') ? (int)$p[8] : 0;
                $statusDesc = $p[9] ?? '';
                $homeId = $p[11] ?? ''; $homeName = $p[12] ?? 'Team 1';
                $awayId = $p[15] ?? ''; $awayName = $p[16] ?? 'Team 2';
                $sc = $parseJson(19);
                $extraScores = $parseJson(20); // Contains innings [[team, runs, overs, wickets], ...]
                $hScore = isset($sc['ft'][0]) ? (int)$sc['ft'][0] : null;
                $aScore = isset($sc['ft'][1]) ? (int)$sc['ft'][1] : null;
                $periodBreakdown = $sc;
                break;

            case 'ice_hockey':
                $countryName = $p[4] ?? 'International';
                $tourName = $p[7] ?? 'Ice Hockey';
                $statusCode = is_numeric($p[10] ?? '') ? (int)$p[10] : 0;
                $homeId = $p[11] ?? ''; $homeName = $p[12] ?? 'Home';
                $awayId = $p[18] ?? ''; $awayName = $p[19] ?? 'Away';
                $venueName = $p[30] ?? '';
                $sc = $parseJson(26);
                // Use OT/SO final score if regulation ended in a tie and OT/SO was played
                $hScore = isset($sc['ft'][0]) ? (int)$sc['ft'][0] : null;
                $aScore = isset($sc['ft'][1]) ? (int)$sc['ft'][1] : null;
                if (isset($sc['ot'][0], $sc['ot'][1]) && ($sc['ot'][0] > 0 || $sc['ot'][1] > 0)) {
                    $statusDesc = 'AOT';
                    if ($hScore === $aScore) {
                        $hScore += (int)$sc['ot'][0];
                        $aScore += (int)$sc['ot'][1];
                    }
                }
                if (isset($sc['so'][0], $sc['so'][1]) && ($sc['so'][0] > 0 || $sc['so'][1] > 0)) {
                    $statusDesc = 'ASO';
                    if ($hScore === $aScore) {
                        $hScore += ($sc['so'][0] > $sc['so'][1] ? 1 : 0);
                        $aScore += ($sc['so'][1] > $sc['so'][0] ? 1 : 0);
                    }
                }
                $periodBreakdown = $sc;
                break;

            case 'table_tennis':
                $tourName = $p[4] ?? 'Table Tennis';
                $countryName = $p[6] ?? 'International';
                $statusCode = is_numeric($p[7] ?? '') ? (int)$p[7] : 0;
                $statusDesc = $p[9] ?? ($p[8] ?? '');
                $homeId = $p[10] ?? ''; $homeName = $p[11] ?? 'Player 1';
                $awayId = $p[14] ?? ''; $awayName = $p[15] ?? 'Player 2';
                $sc = $parseJson(18);
                $hScore = isset($sc['ft'][0]) ? (int)$sc['ft'][0] : null;
                $aScore = isset($sc['ft'][1]) ? (int)$sc['ft'][1] : null;
                $periodBreakdown = $sc;
                break;
        }

        // Compute total points for set-based sports (Badminton, Volleyball, Table Tennis)
        if (in_array($sportKey, ['badminton', 'volleyball', 'table_tennis']) && is_array($periodBreakdown)) {
            $hp = 0; $ap = 0;
            for ($s = 1; $s <= 7; $s++) {
                if (isset($periodBreakdown["p{$s}"][0], $periodBreakdown["p{$s}"][1])) {
                    $hp += (int)$periodBreakdown["p{$s}"][0];
                    $ap += (int)$periodBreakdown["p{$s}"][1];
                }
            }
            if ($hp > 0 || $ap > 0) $totalPoints = ['home' => $hp, 'away' => $ap];
        }

        // Accurate State Classification (Check Special/Retired/Canceled BEFORE Live!)
        $descUpper = strtoupper($statusDesc);
        $stateCat = 'Upcoming';
        if (in_array($statusCode, [11, 12, 13, 14, 15, 16, 17, 18, 60, 70, 80, 90]) || str_contains($descUpper, 'CANCEL') || str_contains($descUpper, 'POSTPON') || str_contains($descUpper, 'RETIR') || str_contains($descUpper, 'WALKOVER') || str_contains($descUpper, 'ABANDON')) {
            $stateCat = 'Special (' . ($statusDesc ?: 'Postponed/Cancelled') . ')';
        } elseif ($statusCode === 10 || $statusCode === 100 || $statusCode === 110 || str_contains($descUpper, 'ENDED') || str_contains($descUpper, 'COMPLETED') || $descUpper === 'AOT' || $descUpper === 'ASO') {
            $stateCat = 'Finished';
        } elseif ($forceLive || ($statusCode >= 2 && $statusCode <= 9) || ($statusCode >= 21 && $statusCode <= 59)) {
            $stateCat = 'Live';
        }

        // Sport-specific live clock / set / inning label
        $readtimeLabel = null;
        if ($stateCat === 'Live') {
            if ($sportKey === 'basketball') {
                $readtimeLabel = self::formatBasketballLiveClock($statusCode, $remainSec);
            } elseif (in_array($sportKey, ['tennis', 'badminton', 'volleyball', 'table_tennis']) && $statusCode >= 51 && $statusCode <= 57) {
                $readtimeLabel = 'Set ' . ($statusCode - 50);
            } else {
                $readtimeLabel = $statusDesc ?: 'LIVE';
            }
        } elseif ($statusCode === 110) {
            $readtimeLabel = 'OT';
        }

        $iso = $ts > 0 ? gmdate('Y-m-d\TH:i:s\Z', $ts) : null;
        $homeLogo = self::teamLogo($sportKey, $homeId);
        $awayLogo = self::teamLogo($sportKey, $awayId);

        $payload = [
            'id' => $id,
            'taxonomy' => [
                'sport' => ['name' => SportsMatch::SPORTS[$sportKey]['name'] ?? $sportKey],
                'country_region' => ['name' => $countryName],
                'tournament_league' => ['id' => $tourId, 'name' => $tourName],
            ],
            'entities' => [
                'teams_or_players' => [
                    'home' => ['id' => $homeId, 'name' => $homeName, 'logo_url' => $homeLogo],
                    'away' => ['id' => $awayId, 'name' => $awayName, 'logo_url' => $awayLogo],
                ],
                'venue' => $venueName ? ['name' => $venueName, 'city' => $venueCity] : [],
                'squad_roster' => ['home' => [], 'away' => []],
            ],
            'match_timeline' => [
                'state_category' => $stateCat,
                'status_code' => $statusCode,
                'status_description' => $statusDesc ?: strtolower($stateCat),
                'start_timestamp' => $ts,
                'start_time_iso' => $iso,
                'readtime' => [
                    'minute' => $readtimeLabel,
                    'remain_seconds' => $remainSec,
                ],
                'scores' => [
                    'home_total' => $hScore,
                    'away_total' => $aScore,
                    'period_breakdown' => $periodBreakdown,
                    'extra_scores' => $extraScores,
                    'total_points' => $totalPoints,
                ],
            ],
            'match_center' => ['live_events_summary' => [], 'match_statistics' => []],
        ];

        SportsMatch::updateOrCreate(
            ['external_id' => $id, 'source' => 'uniscore'],
            [
                'sport_key' => $sportKey,
                'tournament_name' => $tourName,
                'tournament_country' => $countryName,
                'home_team' => $homeName,
                'away_team' => $awayName,
                'home_logo' => $homeLogo,
                'away_logo' => $awayLogo,
                'home_score' => $hScore,
                'away_score' => $aScore,
                'state_category' => $stateCat,
                'status_description' => $statusDesc ?: strtolower($stateCat),
                'match_date' => $dateStr,
                'start_time_iso' => $iso,
                'readtime' => $readtimeLabel,
                'payload' => $payload,
            ]
        );
        return true;
    }

    public function getTopLeagues(string $sportKey = 'football'): array
    {
        $slug = self::SPORT_SLUGS[$sportKey] ?? 'football';
        return Cache::remember("uniscore_top_leagues_{$slug}", 1800, function () use ($slug, $sportKey) {
            $res = $this->get("{$slug}/competition/top-leagues/lang/vi") ?: $this->get("{$slug}/competition/top-leagues");
            $items = $res['data'] ?? [];
            if (!is_array($items)) return [];

            $out = [];
            foreach (array_slice($items, 0, 16) as $item) {
                $id = $item['id'] ?? '';
                if (!$id) continue;
                $out[] = [
                    'id' => $id,
                    'name' => $item['name'] ?? 'Unknown',
                    'country' => $item['country']['name'] ?? '',
                    'status' => $item['current_status'] ?? '',
                    'logo' => self::tournamentLogo($sportKey, $id),
                ];
            }
            return $out;
        });
    }

    public function getCategories(string $sportKey = 'football'): array
    {
        $slug = self::SPORT_SLUGS[$sportKey] ?? 'football';
        return Cache::remember("uniscore_categories_{$slug}", 1800, function () use ($slug, $sportKey) {
            $ep = $slug === 'football' ? "sport/football/categories/location/VN" : "sport/{$slug}/categories";
            $res = $this->get($ep);
            $cats = $res['data']['categories'] ?? ($res['data'] ?? []);
            if (!is_array($cats)) return [];

            $out = [];
            foreach ($cats as $c) {
                if (!is_array($c) || empty($c['id'])) continue;
                $out[] = [
                    'id' => $c['id'],
                    'name' => $c['name'] ?? '',
                    'slug' => $c['slug'] ?? '',
                    'totalEvents' => $c['totalEvents'] ?? 0,
                    'liveEvents' => $c['liveEvents'] ?? 0,
                    'flag' => self::countryFlag($sportKey, $c['id']),
                ];
            }
            return $out;
        });
    }

    public function getLeaguesByCategory(string $sportKey, string $categoryId): array
    {
        $slug = self::SPORT_SLUGS[$sportKey] ?? 'football';
        return Cache::remember("uniscore_cat_leagues_{$slug}_{$categoryId}", 1800, function () use ($slug, $sportKey, $categoryId) {
            $ep = $slug === 'football'
                ? "football/category/{$categoryId}/unique-tournaments/locale/VN"
                : "{$slug}/category/{$categoryId}/unique-tournaments";
            $res = $this->get($ep);
            $groups = $res['data']['groups'] ?? [];
            $out = [];
            foreach ($groups as $g) {
                foreach ($g['uniqueTournaments'] ?? [] as $t) {
                    $id = $t['id'] ?? '';
                    if (!$id) continue;
                    $out[] = [
                        'id' => $id,
                        'name' => $t['name'] ?? '',
                        'slug' => $t['slug'] ?? '',
                        'season_id' => $t['season_id'] ?? '',
                        'current_status' => $t['current_status'] ?? '',
                        'totalEvents' => $t['totalEvents'] ?? 0,
                        'logo' => self::tournamentLogo($sportKey, $id),
                    ];
                }
            }
            return $out;
        });
    }

    /**
     * Full Tournament Detail Page data (Overview, Upcoming + Finished Matches auto-saved to DB,
     * Standings, Transfers, Top Players, Top Teams, Overview Stats, Honors/Cup).
     */
    public function getTournamentOverview(string $sportKey, string $compId, ?string $seasonId = null, int $page = 1): array
    {
        $slug = self::SPORT_SLUGS[$sportKey] ?? 'football';
        $cacheKey = "uniscore_comp_v2_{$slug}_{$compId}_" . ($seasonId ?? 'latest') . "_p{$page}";

        return Cache::remember($cacheKey, 300, function () use ($slug, $sportKey, $compId, $seasonId, $page) {
            $infoRes = $this->get("{$slug}/unique-tournament/{$compId}");
            $seasonsRes = $this->get("{$slug}/unique-tournament/{$compId}/seasons");

            $info = $infoRes['data']['uniqueTournament'] ?? ($infoRes['data'] ?? []);
            $seasons = $seasonsRes['data']['seasons'] ?? [];
            $activeSeason = $seasonId ?: ($seasons[0]['id'] ?? null);

            $standings = [];
            $upcomingEvents = [];
            $finishedEvents = [];
            $transfers = [];
            $topPlayers = [];
            $topTeams = [];
            $overviewStats = [];
            $champions = [];

            $isBasketball = ($sportKey === 'basketball');

            // Upcoming matches for this competition
            if ($isBasketball && $activeSeason) {
                $upRes = $this->get("basketball/unique-tournament/{$compId}/season/{$activeSeason}/events/next/" . max(0, $page - 1));
                $upcomingEvents = $upRes['data']['events'] ?? [];
            } else {
                $upRes = $this->get("{$slug}/unique-tournament/{$compId}/events/upcoming/page/{$page}");
                $upcomingEvents = $upRes['data']['events'] ?? [];
            }

            if ($activeSeason) {
                // Finished matches for this season
                if ($isBasketball) {
                    $finRes = $this->get("basketball/unique-tournament/{$compId}/season/{$activeSeason}/events/last/" . max(0, $page - 1));
                    $finishedEvents = $finRes['data']['events'] ?? [];
                } else {
                    $finRes = $this->get("{$slug}/unique-tournament/{$compId}/seasons/{$activeSeason}/events/finished/page/{$page}");
                    $finishedEvents = $finRes['data']['events'] ?? [];
                    if (empty($upcomingEvents) && empty($finishedEvents)) {
                        $rRes = $this->get("{$slug}/unique-tournament/{$compId}/season/{$activeSeason}/events/round/1");
                        $finishedEvents = $rRes['data']['events'] ?? [];
                    }
                }

                // Cache-Through: Persist fetched competition matches straight into our SQLite DB!
                foreach (array_merge($upcomingEvents, $finishedEvents) as $ev) {
                    if (is_array($ev) && !empty($ev['id'])) {
                        $this->upsertFootballEvent($ev, $sportKey);
                    }
                }

                // Standings
                if ($isBasketball) {
                    $stRes = $this->get("basketball/unique-tournament/{$compId}/season/{$activeSeason}/standings");
                    $standings = $stRes['data']['standings'] ?? [];
                } else {
                    $stRes = $this->get("{$slug}/unique-tournament/{$compId}/season/{$activeSeason}/standings/total");
                    $standings = $stRes['data']['standings'] ?? [];
                }

                // Transfers ("Chuyển Nhượng")
                $trRes = $this->get("{$slug}/unique-tournament/{$compId}/{$activeSeason}/transfers");
                $transfers = $trRes['data']['transfers'] ?? [];

                // Top Players ("Cầu Thủ Nổi Bật")
                $tpRes = $this->get("{$slug}/unique-tournament/{$compId}/season/{$activeSeason}/top-players/overall");
                $topPlayers = $tpRes['data']['topPlayers'] ?? ($tpRes['data'] ?? []);

                // Top Teams ("Đội Bóng Hàng Đầu")
                $ttEndpoint = $isBasketball
                    ? "basketball/unique-tournament/{$compId}/season/{$activeSeason}/top-teams/overall"
                    : "{$slug}/unique-tournament/{$compId}/season/{$activeSeason}/top-teams/overall-v2";
                $ttRes = $this->get($ttEndpoint);
                $topTeams = $ttRes['data']['top_teams'] ?? ($ttRes['data'] ?? []);

                // Overview Stats ("Thống Kê Hàng Đầu")
                $osRes = $this->get("{$slug}/unique-tournament/{$compId}/season/{$activeSeason}/overview-statistics");
                $overviewStats = $osRes['data'] ?? [];

                // Champions / Honors ("Cúp")
                $chRes = $this->get("{$slug}/unique-tournament/{$compId}/season/{$activeSeason}/champions");
                $champions = $chRes['data'] ?? [];

                // Knockout Bracket ("Nhánh Đấu")
                $brRes = $this->get("{$slug}/unique-tournament/{$compId}/season/{$activeSeason}/cuptrees/structured")
                    ?: $this->get("{$slug}/season/{$activeSeason}/cuptrees/structured");
                $bracket = $brRes['data'] ?? null;
            }

            return [
                'id' => $compId,
                'sport_key' => $sportKey,
                'logo' => self::tournamentLogo($sportKey, $compId),
                'info' => $info,
                'seasons' => $seasons,
                'active_season' => $activeSeason,
                'active_page' => $page,
                'standings' => $standings,
                'upcoming_events' => $upcomingEvents,
                'finished_events' => $finishedEvents,
                'transfers' => $transfers,
                'top_players' => $topPlayers,
                'top_teams' => $topTeams,
                'overview_stats' => $overviewStats,
                'champions' => $champions,
                'bracket' => $bracket ?? null,
            ];
        });
    }

    /**
     * Extended Match Tabs: H2H (Đối Đầu), Analytics (Phân Tích Dữ Liệu),
     * AI Prediction (AI Dự Đoán), and Bracket (Nhánh Đấu) for Football & Basketball.
     * DB-FIRST: Finished matches with saved `extended_cached` NEVER hit external API again!
     */
    public function getMatchExtendedData(string $sportKey, string $eventId, ?string $homeId = null, ?string $awayId = null, ?string $compId = null, ?string $seasonId = null): array
    {
        $dbMatch = SportsMatch::where('external_id', $eventId)->first();
        $isFinished = $dbMatch && str_contains((string)$dbMatch->state_category, 'Finished');

        // 1. DB-FIRST CHECK: If match is Finished and extended data is already saved in SQLite -> serve immediately!
        if ($isFinished && !empty($dbMatch->payload['extended_cached'])) {
            $cached = $dbMatch->payload['extended_cached'];
            $cached['_from_db'] = true;
            return $cached;
        }

        $slug = self::SPORT_SLUGS[$sportKey] ?? 'football';
        $cacheKey = "uniscore_ext_{$slug}_{$eventId}";

        $result = Cache::remember($cacheKey, 180, function () use ($slug, $sportKey, $eventId, $homeId, $awayId, $compId, $seasonId) {
            $startTs = 0;
            $ev = $this->get("{$slug}/event/{$eventId}")['data']['event'] ?? null;
            if ($ev) {
                $homeId = $homeId ?: ($ev['homeTeam']['id'] ?? null);
                $awayId = $awayId ?: ($ev['awayTeam']['id'] ?? null);
                $compId = $compId ?: ($ev['tournament']['id'] ?? ($ev['uniqueTournament']['id'] ?? null));
                $seasonId = $seasonId ?: ($ev['season']['id'] ?? ($ev['season_id'] ?? ($ev['seasonId'] ?? ($ev['tournament']['season_id'] ?? null))));
                $startTs = (int)($ev['startTimestamp'] ?? 0);
            }

            // 1. H2H (Đối Đầu) — supports BOTH standard `/h2h` and `/start-time/{ts}/h2h` (teamDuel)
            $h2hEvents = [];
            $teamDuel = null;
            if ($homeId && $awayId) {
                $h2hRes = $this->get("{$slug}/event/{$eventId}/home/{$homeId}/away/{$awayId}/h2h");
                $rawH2h = $h2hRes['data']['events'] ?? [];
                foreach ($rawH2h as $e) {
                    $ht = $e['homeTeam'] ?? [];
                    $at = $e['awayTeam'] ?? [];
                    $tourObj = $e['tournament'] ?? ($e['uniqueTournament'] ?? []);
                    $hs = $e['homeScore']['display'] ?? ($e['homeScore']['current'] ?? ($e['scores']['ft'][0] ?? null));
                    $as = $e['awayScore']['display'] ?? ($e['awayScore']['current'] ?? ($e['scores']['ft'][1] ?? null));
                    $h2hEvents[] = [
                        'id' => $e['id'] ?? '',
                        'timestamp' => (int)($e['startTimestamp'] ?? 0),
                        'tournament_id' => $tourObj['id'] ?? '',
                        'tournament_name' => $tourObj['name'] ?? '',
                        'home_id' => $ht['id'] ?? '',
                        'home_name' => $ht['name'] ?? '',
                        'home_logo' => self::teamLogo($sportKey, $ht['id'] ?? ''),
                        'away_id' => $at['id'] ?? '',
                        'away_name' => $at['name'] ?? '',
                        'away_logo' => self::teamLogo($sportKey, $at['id'] ?? ''),
                        'home_score' => is_numeric($hs) ? (int)$hs : $hs,
                        'away_score' => is_numeric($as) ? (int)$as : $as,
                        'status_type' => $e['status']['type'] ?? '',
                    ];
                }
                if (empty($h2hEvents) && $startTs > 0) {
                    $duelRes = $this->get("{$slug}/event/{$eventId}/home/{$homeId}/away/{$awayId}/start-time/{$startTs}/h2h");
                    $teamDuel = $duelRes['data']['teamDuel'] ?? null;
                }
            }

            // 2. Analytics (Phân Tích Dữ Liệu) & AI Prediction (AI Dự Đoán)
            $analytics = null;
            $prediction = null;
            if ($sportKey === 'football') {
                $anRes = $this->get("football/event/{$eventId}/analytics");
                if (is_array($anRes) && !isset($anRes['statusCode'])) {
                    $analytics = $anRes;
                }
                $prRes = $this->get("football/event/{$eventId}/prediction/pre")
                    ?: $this->get("football/event/{$eventId}/prediction/post");
                $prediction = $prRes['data'] ?? null;
            }

            // 3. Bracket (Nhánh Đấu)
            $bracket = null;
            if ($compId && $seasonId) {
                $brRes = $this->get("{$slug}/unique-tournament/{$compId}/season/{$seasonId}/cuptrees/structured")
                    ?: $this->get("{$slug}/season/{$seasonId}/cuptrees/structured");
                $bracket = $brRes['data'] ?? null;
            }

            return [
                'home_id' => $homeId,
                'away_id' => $awayId,
                'comp_id' => $compId,
                'h2h' => $h2hEvents,
                'team_duel' => $teamDuel,
                'analytics' => $analytics,
                'prediction' => $prediction,
                'bracket' => $bracket,
                '_from_db' => false,
            ];
        });

        // 2. PERSIST TO SQLITE DB so future visits never have to fetch from source again!
        if ($dbMatch) {
            $payload = $dbMatch->payload ?? [];
            $payload['extended_cached'] = $result;
            $dbMatch->payload = $payload;
            $dbMatch->save();
        }

        return $result;
    }

    /**
     * Cache-Through Match Detail: Fetch live event detail, incidents, lineups, statistics,
     * and mini-standings from UniScore, persist them into `sports_matches.payload`, and return immediately!
     * DB-FIRST: Finished matches with saved `detail_cached` NEVER hit external API again!
     */
    public function getMatchFullDetail(string $sportKey, string $eventId, ?string $homeId = null, ?string $awayId = null): array
    {
        $dbMatch = SportsMatch::where('external_id', $eventId)->first();
        $isFinished = $dbMatch && str_contains((string)$dbMatch->state_category, 'Finished');

        // 1. DB-FIRST CHECK: If match is Finished and full detail is already saved in SQLite -> serve immediately!
        if ($isFinished && !empty($dbMatch->payload['detail_cached'])) {
            $cached = $dbMatch->payload['detail_cached'];
            $cached['_from_db'] = true;
            return $cached;
        }

        $slug = self::SPORT_SLUGS[$sportKey] ?? 'football';
        return Cache::remember("uniscore_match_v4_{$slug}_{$eventId}", 15, function () use ($slug, $sportKey, $eventId, $homeId, $awayId, $dbMatch) {
            $detail = $this->get("{$slug}/event/{$eventId}")['data']['event'] ?? null;
            $incRaw = $this->get("{$slug}/event/{$eventId}/incidents");
            $incidents = [];
            $cricketInnings = [];
            if (is_array($incRaw['data'] ?? null)) {
                if (isset($incRaw['data']['incidents']) && is_array($incRaw['data']['incidents'])) {
                    $incidents = $incRaw['data']['incidents'];
                } elseif (isset($incRaw['data'][0]['overs'])) {
                    // Cricket Over-by-Over Ball-by-Ball Play-by-Play!
                    $cricketInnings = $incRaw['data'];
                } elseif (array_is_list($incRaw['data'])) {
                    $incidents = $incRaw['data'];
                }
            }
            $lineupsRaw = $this->get("{$slug}/event/{$eventId}/lineups")['data'] ?? null;

            $hId = $homeId ?: ($detail['homeTeam']['id'] ?? null);
            $aId = $awayId ?: ($detail['awayTeam']['id'] ?? null);
            $stats = [];
            if ($hId && $aId) {
                $stats = $this->get("{$slug}/event/{$eventId}/home/{$hId}/away/{$aId}/statistics")['data']['statistics'] ?? [];
            }
            if (empty($stats)) {
                $stats = $this->get("{$slug}/event/{$eventId}/statistics")['data']['statistics'] ?? [];
            }

            // Enrich lineups with real encoded player avatars
            $lineups = null;
            if (is_array($lineupsRaw)) {
                $lineups = ['confirmed' => $lineupsRaw['confirmed'] ?? false, 'home' => ['formation' => '', 'players' => []], 'away' => ['formation' => '', 'players' => []]];
                foreach (['home', 'away'] as $side) {
                    if (!empty($lineupsRaw[$side]) && is_array($lineupsRaw[$side])) {
                        $lineups[$side]['formation'] = $lineupsRaw[$side]['formation'] ?? '';
                        foreach (($lineupsRaw[$side]['players'] ?? []) as $p) {
                            $pl = $p['player'] ?? [];
                            $pid = $pl['id'] ?? '';
                            $lineups[$side]['players'][] = [
                                'id' => $pid,
                                'name' => $pl['fullName'] ?? ($pl['name'] ?? 'Player'),
                                'short_name' => $pl['name'] ?? '',
                                'avatar' => self::playerAvatar($sportKey, $pid),
                                'shirt_number' => $p['shirtNumber'] ?? '',
                                'position' => $p['position'] ?? ($pl['position'] ?? ''),
                                'is_substitute' => (bool)($p['substitute'] ?? false),
                                'market_value' => $pl['market_value'] ?? '',
                                'rating' => $p['statistics']['rating'] ?? null,
                                'stats' => $p['statistics'] ?? [],
                            ];
                        }
                    }
                }
            }

            // Mini Standings: pick the exact group containing home or away team (for multi-group cups!)
            $standingsRows = [];
            $compId = $detail['tournament']['id'] ?? ($detail['uniqueTournament']['id'] ?? null);
            $seasonId = $detail['season']['id'] ?? ($detail['season_id'] ?? ($detail['seasonId'] ?? ($detail['tournament']['season_id'] ?? null)));
            if ($compId && $seasonId) {
                $stEp = ($sportKey === 'basketball')
                    ? "basketball/unique-tournament/{$compId}/season/{$seasonId}/standings"
                    : "{$slug}/unique-tournament/{$compId}/season/{$seasonId}/standings/total";
                $stRes = $this->get($stEp);
                $groups = $stRes['data']['standings'] ?? [];
                $targetGroup = $groups[0] ?? null;
                foreach ($groups as $g) {
                    foreach (($g['rows'] ?? []) as $r) {
                        $tid = $r['team']['id'] ?? '';
                        if ($tid && ($tid === $hId || $tid === $aId)) {
                            $targetGroup = $g;
                            break 2;
                        }
                    }
                }
                if (!empty($targetGroup['rows'])) {
                    foreach ($targetGroup['rows'] as $r) {
                        $tm = $r['team'] ?? [];
                        $m = (int)($r['matches'] ?? 0);
                        $w = (int)($r['wins'] ?? 0);
                        $standingsRows[] = [
                            'position' => $r['position'] ?? '-',
                            'team_id' => $tm['id'] ?? '',
                            'team_name' => $tm['name'] ?? '',
                            'team_logo' => self::teamLogo($sportKey, $tm['id'] ?? ''),
                            'matches' => $m,
                            'wins' => $w,
                            'draws' => $r['draws'] ?? 0,
                            'losses' => $r['losses'] ?? 0,
                            'diff' => ($r['scoresFor'] ?? 0) - ($r['scoresAgainst'] ?? 0),
                            'points' => $r['points'] ?? ($m > 0 ? number_format($w / $m, 3) : '.000'),
                            'pct' => $m > 0 ? number_format($w / $m, 3) : '.000',
                        ];
                    }
                }
            }

            $detailResult = [
                'event' => $detail,
                'incidents' => $incidents,
                'cricket_innings' => $cricketInnings,
                'lineups' => $lineups,
                'statistics' => $stats,
                'standings' => $standingsRows,
                '_from_db' => false,
            ];

            // Persist enriched detail into local SQLite DB (`sports_matches`)
            if ($dbMatch) {
                $payload = $dbMatch->payload ?? [];
                if ($detail) {
                    $hScore = $detail['homeScore']['display'] ?? ($detail['homeScore']['current'] ?? $dbMatch->home_score);
                    $aScore = $detail['awayScore']['display'] ?? ($detail['awayScore']['current'] ?? $dbMatch->away_score);
                    $dbMatch->home_score = $hScore;
                    $dbMatch->away_score = $aScore;
                    $stType = strtolower((string)($detail['status']['type'] ?? ''));
                    if ($stType === 'finished') {
                        $dbMatch->state_category = 'Finished';
                    }
                    if (!empty($detail['venue'])) $payload['entities']['venue'] = $detail['venue'];
                    if (!empty($detail['referee']['name'])) {
                        $payload['entities']['match_officials'] = [['name' => $detail['referee']['name'], 'role' => 'Main Referee']];
                    }
                    $payload['match_timeline']['scores']['home_total'] = $hScore;
                    $payload['match_timeline']['scores']['away_total'] = $aScore;
                    if (!empty($detail['homeScore'])) {
                        $payload['match_timeline']['scores']['period_breakdown'] = [
                            'home' => $detail['homeScore'],
                            'away' => $detail['awayScore'] ?? [],
                        ];
                    }
                }
                if ($lineups) {
                    $payload['entities']['squad_roster'] = [
                        'home' => $lineups['home']['players'],
                        'away' => $lineups['away']['players'],
                    ];
                    $payload['match_center']['confirmed_lineups'] = [
                        'is_confirmed' => $lineups['confirmed'],
                        'home_formation' => $lineups['home']['formation'],
                        'away_formation' => $lineups['away']['formation'],
                    ];
                }
                $payload['match_center']['live_events_summary'] = $incidents;
                $payload['match_center']['match_statistics'] = $stats;
                // Permanent DB snapshot so Finished matches never need to call source API again!
                $payload['detail_cached'] = $detailResult;
                $dbMatch->payload = $payload;
                $dbMatch->save();
            }

            return $detailResult;
        });
    }
}
