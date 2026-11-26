<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\SportsMatch;
use App\Services\UniScoreApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SportsApiController extends Controller
{
    use ApiResponse;

    public function __construct(private UniScoreApiService $uniscore)
    {
    }

    private function normalizeSport(string $sportKey): ?string
    {
        return array_key_exists($sportKey, SportsMatch::SPORTS) ? $sportKey : null;
    }

    private function formatMatch(SportsMatch $m): array
    {
        $payload = is_array($m->payload) ? $m->payload : [];
        $taxonomy = $payload['taxonomy'] ?? [];
        $tourObj = $taxonomy['tournament_league'] ?? [];
        $countryObj = $taxonomy['country_region'] ?? [];
        $tourId = $tourObj['id'] ?? null;

        $teamsObj = $payload['entities']['teams_or_players'] ?? ($payload['participants'] ?? []);
        $homeObj = $teamsObj['home'] ?? [];
        $awayObj = $teamsObj['away'] ?? [];

        $timeline = $payload['match_timeline'] ?? ($payload['timing'] ?? []);
        $readtimeObj = is_array($timeline['readtime'] ?? null) ? $timeline['readtime'] : [];
        $scoresObj = $timeline['scores'] ?? ($payload['scores'] ?? []);
        $periodBreakdown = $scoresObj['period_breakdown'] ?? ($scoresObj['periods'] ?? null);

        $homeBreakdown = is_array($periodBreakdown['home'] ?? null) ? $periodBreakdown['home'] : [];
        $awayBreakdown = is_array($periodBreakdown['away'] ?? null) ? $periodBreakdown['away'] : [];

        return [
            'id' => $m->id,
            'event_id' => $m->external_id ?: ($payload['id'] ?? null),
            'slug' => $payload['slug'] ?? null,
            'sport' => $m->sport_key,
            'match_date' => $m->match_date,
            'start_time_iso' => $m->start_time_iso,
            'start_timestamp' => $timeline['start_timestamp'] ?? ($m->start_time_iso ? strtotime($m->start_time_iso) : null),
            'status' => [
                'category' => $m->state_category,
                'description' => $m->status_description ?: ($timeline['status_description'] ?? null),
                'code' => $timeline['status_code'] ?? null,
                'live_minute' => $m->readtime ?: ($readtimeObj['minute'] ?? null),
                'period_start_ts' => $readtimeObj['period_start_ts'] ?? null,
                'period_state' => $readtimeObj['period_state'] ?? null,
            ],
            'tournament' => [
                'id' => $tourId,
                'name' => $m->tournament_name,
                'slug' => $tourObj['slug'] ?? null,
                'round' => $tourObj['round'] ?? null,
                'country' => $m->tournament_country ?: ($countryObj['name'] ?? null),
                'country_code' => $m->country_code ?: null,
                'logo' => $tourId ? UniScoreApiService::tournamentLogo($m->sport_key, (string) $tourId) : null,
            ],
            'home' => [
                'id' => $homeObj['id'] ?? null,
                'name' => $m->home_team,
                'score' => $m->home_score,
                'logo' => $m->home_logo ?: ($homeObj['logo_url'] ?? ($homeObj['logo'] ?? null)),
                'red_cards' => $homeBreakdown['red_card'] ?? ($homeObj['red_cards'] ?? null),
                'yellow_cards' => $homeBreakdown['yellow_card'] ?? null,
                'corners' => $homeBreakdown['corner'] ?? null,
            ],
            'away' => [
                'id' => $awayObj['id'] ?? null,
                'name' => $m->away_team,
                'score' => $m->away_score,
                'logo' => $m->away_logo ?: ($awayObj['logo_url'] ?? ($awayObj['logo'] ?? null)),
                'red_cards' => $awayBreakdown['red_card'] ?? ($awayObj['red_cards'] ?? null),
                'yellow_cards' => $awayBreakdown['yellow_card'] ?? null,
                'corners' => $awayBreakdown['corner'] ?? null,
            ],
            'period_scores' => $periodBreakdown,
            'is_pinned' => (bool) $m->is_pinned,
            'priority_order' => (int) ($m->priority_order ?? 0),
            'updated_at' => $m->updated_at?->toIso8601String(),
        ];
    }

    /**
     * GET /api/v1/health
     */
    public function health(): JsonResponse
    {
        return $this->ok([
            'status' => 'healthy',
            'api_version' => 'v1',
            'server_time' => time(),
            'server_time_iso' => now()->toIso8601String(),
            'total_matches_cached' => SportsMatch::count(),
            'live_matches_count' => SportsMatch::where('state_category', 'like', '%Live%')->count(),
            'supported_sports_count' => count(SportsMatch::SPORTS),
        ]);
    }

    /**
     * GET /api/v1/settings
     */
    public function settings(): JsonResponse
    {
        $all = SiteSetting::allCached();
        return $this->ok([
            'site_name' => $all['site_name'],
            'site_tagline' => $all['site_tagline'],
            'site_logo_url' => $all['site_logo_url'],
            'seo_description' => $all['seo_description'],
            'notice_banner' => $all['notice_banner'],
            'footer_text' => $all['footer_text'],
            'contact_email' => $all['contact_email'],
            'support_url' => $all['support_url'],
            'default_sport' => $all['default_sport'],
        ]);
    }

    /**
     * GET /api/v1/sports
     */
    public function sports(): JsonResponse
    {
        $counts = SportsMatch::select(
            'sport_key',
            DB::raw('COUNT(*) as total_count'),
            DB::raw("SUM(CASE WHEN state_category LIKE '%Live%' THEN 1 ELSE 0 END) as live_count")
        )
            ->groupBy('sport_key')
            ->get()
            ->keyBy('sport_key');

        $list = [];
        foreach (SportsMatch::SPORTS as $key => $info) {
            $row = $counts->get($key);
            $list[] = [
                'key' => $key,
                'name' => $info['name'],
                'icon' => $info['icon'],
                'live_count' => (int) ($row->live_count ?? 0),
                'total_cached' => (int) ($row->total_count ?? 0),
            ];
        }

        return $this->ok($list, [
            'count' => count($list),
            'server_time' => time(),
        ]);
    }

    /**
     * GET /api/v1/matches
     * Query params: sport, date, date_from, date_to, status (all|live|finished|upcoming),
     *               league, league_id, q, page, per_page, group_by_league (0|1), auto_sync (0|1)
     */
    public function matches(Request $request): JsonResponse
    {
        $sportKey = (string) $request->query('sport', 'football');
        if (!$this->normalizeSport($sportKey)) {
            return $this->fail("Môn thể thao không hợp lệ: {$sportKey}", 422, [
                'supported_sports' => array_keys(SportsMatch::SPORTS),
            ]);
        }

        $singleDate = $request->query('date');
        $dateFrom = (string) ($request->query('date_from') ?: ($singleDate ?: date('Y-m-d')));
        $dateTo = (string) ($request->query('date_to') ?: ($singleDate ?: $dateFrom));
        if ($dateTo < $dateFrom) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));
        $leagueFilter = trim((string) $request->query('league', ''));
        $leagueId = trim((string) $request->query('league_id', ''));
        $autoSync = $request->boolean('auto_sync', true);
        $groupByLeague = $request->boolean('group_by_league', false);
        $perPage = min(200, max(1, (int) $request->query('per_page', 50)));

        $autoSyncedCount = 0;
        if ($autoSync) {
            $todayStr = date('Y-m-d');
            if ($status === 'live' || ($dateFrom <= $todayStr && $dateTo >= $todayStr)) {
                $this->uniscore->syncLiveMatches($sportKey);
            }
            if ($status !== 'live') {
                $cursorTs = strtotime($dateFrom);
                $endTs = strtotime($dateTo);
                $dayCount = 0;
                while ($cursorTs !== false && $endTs !== false && $cursorTs <= $endTs && $dayCount < 7) {
                    $autoSyncedCount += $this->uniscore->ensureDateMatchesInDb($sportKey, date('Y-m-d', $cursorTs));
                    $cursorTs = strtotime('+1 day', $cursorTs);
                    $dayCount++;
                }
            }
        }

        $query = SportsMatch::where('sport_key', $sportKey);

        if ($status === 'live') {
            $query->where('state_category', 'like', '%Live%');
        } else {
            $query->whereBetween('match_date', [$dateFrom, $dateTo]);
            if ($status === 'upcoming') {
                $query->where('state_category', 'like', '%Upcoming%');
            } elseif ($status === 'finished') {
                $query->where('state_category', 'like', '%Finished%');
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('home_team', 'like', "%{$search}%")
                    ->orWhere('away_team', 'like', "%{$search}%")
                    ->orWhere('tournament_name', 'like', "%{$search}%");
            });
        }

        if ($leagueFilter !== '') {
            $query->where('tournament_name', 'like', "%{$leagueFilter}%");
        }

        if ($leagueId !== '') {
            $query->where('payload', 'like', '%"id":"' . $leagueId . '"%');
        }

        $query->orderByDesc('is_pinned')
            ->orderByDesc('priority_order')
            ->orderBy('match_date')
            ->orderBy('tournament_name')
            ->orderBy('start_time_iso');

        if ($groupByLeague) {
            $items = $query->limit(500)->get()->map(fn (SportsMatch $m) => $this->formatMatch($m));
            $grouped = $items->groupBy(fn (array $item) => $item['tournament']['name'] ?: 'Other')
                ->map(function ($group, $name) {
                    $first = $group->first();
                    return [
                        'tournament' => $first['tournament'],
                        'matches_count' => $group->count(),
                        'matches' => $group->values()->all(),
                    ];
                })
                ->values()
                ->all();

            return $this->ok($grouped, [
                'sport' => $sportKey,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status,
                'grouped' => true,
                'total_matches' => $items->count(),
                'total_leagues' => count($grouped),
                'auto_synced_count' => $autoSyncedCount,
                'server_time' => time(),
            ]);
        }

        $paginator = $query->paginate($perPage);
        $formatted = collect($paginator->items())->map(fn (SportsMatch $m) => $this->formatMatch($m))->values()->all();

        return $this->ok($formatted, [
            'sport' => $sportKey,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => $status,
            'grouped' => false,
            'auto_synced_count' => $autoSyncedCount,
            'server_time' => time(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /api/v1/matches/{sport}/{eventId}
     */
    public function matchDetail(string $sport, string $eventId, Request $request): JsonResponse
    {
        if (!$this->normalizeSport($sport)) {
            return $this->fail("Môn thể thao không hợp lệ: {$sport}", 422);
        }

        $detail = $this->uniscore->getMatchFullDetail(
            $sport,
            $eventId,
            $request->query('home_id'),
            $request->query('away_id')
        );

        return $this->ok($detail, [
            'sport' => $sport,
            'event_id' => $eventId,
            'server_time' => time(),
        ]);
    }

    /**
     * GET /api/v1/matches/{sport}/{eventId}/extended
     */
    public function matchExtended(string $sport, string $eventId, Request $request): JsonResponse
    {
        if (!$this->normalizeSport($sport)) {
            return $this->fail("Môn thể thao không hợp lệ: {$sport}", 422);
        }

        $data = $this->uniscore->getMatchExtendedData(
            $sport,
            $eventId,
            $request->query('home_id'),
            $request->query('away_id'),
            $request->query('comp_id'),
            $request->query('season_id')
        );

        return $this->ok($data, [
            'sport' => $sport,
            'event_id' => $eventId,
            'server_time' => time(),
        ]);
    }

    /**
     * GET /api/v1/live/{sport}
     */
    public function live(string $sport): JsonResponse
    {
        if (!$this->normalizeSport($sport)) {
            return $this->fail("Môn thể thao không hợp lệ: {$sport}", 422);
        }

        $liveMatches = $this->uniscore->syncLiveMatches($sport);

        return $this->ok($liveMatches, [
            'sport' => $sport,
            'live_count' => count($liveMatches),
            'server_time' => time(),
        ]);
    }

    /**
     * GET /api/v1/categories/{sport}
     */
    public function categories(string $sport): JsonResponse
    {
        if (!$this->normalizeSport($sport)) {
            return $this->fail("Môn thể thao không hợp lệ: {$sport}", 422);
        }

        $categories = $this->uniscore->getCategories($sport);

        return $this->ok($categories, [
            'sport' => $sport,
            'count' => count($categories),
        ]);
    }

    /**
     * GET /api/v1/leagues/{sport}/top
     */
    public function topLeagues(string $sport): JsonResponse
    {
        if (!$this->normalizeSport($sport)) {
            return $this->fail("Môn thể thao không hợp lệ: {$sport}", 422);
        }

        $leagues = $this->uniscore->getTopLeagues($sport);

        return $this->ok($leagues, [
            'sport' => $sport,
            'count' => count($leagues),
        ]);
    }

    /**
     * GET /api/v1/leagues/{sport}/category/{categoryId}
     */
    public function leaguesByCategory(string $sport, string $categoryId): JsonResponse
    {
        if (!$this->normalizeSport($sport)) {
            return $this->fail("Môn thể thao không hợp lệ: {$sport}", 422);
        }

        $leagues = $this->uniscore->getLeaguesByCategory($sport, $categoryId);

        return $this->ok($leagues, [
            'sport' => $sport,
            'category_id' => $categoryId,
            'count' => count($leagues),
        ]);
    }

    /**
     * GET /api/v1/competitions/{sport}/{id}
     */
    public function competition(string $sport, string $id, Request $request): JsonResponse
    {
        if (!$this->normalizeSport($sport)) {
            return $this->fail("Môn thể thao không hợp lệ: {$sport}", 422);
        }

        $seasonId = $request->query('season');
        $page = max(1, (int) $request->query('page', 1));
        $dateFrom = $request->query('date_from', '');
        $dateTo = $request->query('date_to', '');

        $comp = $this->uniscore->getTournamentOverview($sport, $id, $seasonId, $page);

        if ($dateFrom || $dateTo) {
            $filterFn = function ($ev) use ($dateFrom, $dateTo) {
                $ts = (int) ($ev['startTimestamp'] ?? 0);
                if (!$ts) {
                    return true;
                }
                $d = date('Y-m-d', $ts);
                if ($dateFrom && $d < $dateFrom) {
                    return false;
                }
                if ($dateTo && $d > $dateTo) {
                    return false;
                }
                return true;
            };
            $comp['upcoming_events'] = array_values(array_filter($comp['upcoming_events'] ?? [], $filterFn));
            $comp['finished_events'] = array_values(array_filter($comp['finished_events'] ?? [], $filterFn));
        }

        return $this->ok($comp, [
            'sport' => $sport,
            'competition_id' => $id,
            'page' => $page,
            'season' => $seasonId,
            'server_time' => time(),
        ]);
    }
}
