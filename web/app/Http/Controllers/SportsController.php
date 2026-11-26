<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SiteSetting;
use App\Models\SportsMatch;
use App\Services\UniScoreApiService;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class SportsController extends Controller
{
    public function __construct(private UniScoreApiService $uniscore)
    {
    }

    /**
     * User-facing Live Score & Match Center (99% UniScore 4-Column Layout + Cache-Through Auto-Crawl + Date Range).
     */
    public function index(Request $request)
    {
        $defaultSport = SiteSetting::getValue('default_sport', 'football');
        $sportKey = $request->query('sport', $defaultSport);
        if (!array_key_exists($sportKey, SportsMatch::SPORTS)) {
            $sportKey = 'football';
        }

        // Support both single `date` and range `date_from`..`date_to`
        $singleDate = $request->query('date');
        $dateFrom = $request->query('date_from') ?: ($singleDate ?: date('Y-m-d'));
        $dateTo = $request->query('date_to') ?: ($singleDate ?: $dateFrom);
        if ($dateTo < $dateFrom) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        $date = $dateFrom;

        $status = $request->query('status', 'all');
        $source = $request->query('source', 'all');
        $search = trim((string) $request->query('q', ''));
        $leagueFilter = trim((string) $request->query('league', ''));

        // ALWAYS sync live matches when viewing Live tab or when today is in selected date range!
        $todayStr = date('Y-m-d');
        if ($status === 'live' || ($dateFrom <= $todayStr && $dateTo >= $todayStr)) {
            $this->uniscore->syncLiveMatches($sportKey);
        }

        // Cache-Through Auto-Crawl: check each date in selected range (max 7 days)
        $autoSyncedCount = 0;
        if ($status !== 'live') {
            $cursorTs = strtotime($dateFrom);
            $endTs = strtotime($dateTo);
            $dayCount = 0;
            while ($cursorTs !== false && $endTs !== false && $cursorTs <= $endTs && $dayCount < 7) {
                $dStr = date('Y-m-d', $cursorTs);
                $autoSyncedCount += $this->uniscore->ensureDateMatchesInDb($sportKey, $dStr);
                $cursorTs = strtotime('+1 day', $cursorTs);
                $dayCount++;
            }
        }

        $query = SportsMatch::where('sport_key', $sportKey);

        if ($status === 'live') {
            // Live tab shows ALL currently live matches for this sport regardless of UTC midnight boundary
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

        $matches = $query->orderByDesc('is_pinned')
            ->orderBy('match_date')
            ->orderBy('tournament_name')
            ->orderBy('start_time_iso')
            ->get();

        foreach ($matches as $m) {
            $payload = $m->payload ?? [];
            $tourId = $payload['taxonomy']['tournament_league']['id'] ?? null;
            $m->tournament_id = $tourId;
            $m->tournament_logo = $tourId ? UniScoreApiService::tournamentLogo($sportKey, $tourId) : null;
        }

        $grouped = $matches->groupBy('tournament_name');
        $sports = SportsMatch::SPORTS;
        $liveCount = SportsMatch::where('state_category', 'like', '%Live%')->count();
        $totalCount = SportsMatch::count();

        $topLeagues = $this->uniscore->getTopLeagues($sportKey);
        $categories = $this->uniscore->getCategories($sportKey);
        $defaultMatch = $matches->first();

        return view('uniscore.index', compact(
            'grouped', 'sports', 'sportKey', 'date', 'dateFrom', 'dateTo', 'status', 'source', 'search',
            'liveCount', 'totalCount', 'topLeagues', 'categories', 'defaultMatch', 'autoSyncedCount'
        ));
    }

    /**
     * Tournament Detail Page (Overview, Fixtures/Results, Standings, Transfers, Top Players, Top Teams, Stats, Cup).
     */
    public function competition(Request $request, string $sportKey, string $compId)
    {
        if (!array_key_exists($sportKey, SportsMatch::SPORTS)) {
            $sportKey = 'football';
        }
        $seasonId = $request->query('season');
        $page = max(1, (int) $request->query('page', 1));
        $tab = $request->query('tab', 'overview');
        $dateFrom = $request->query('date_from', '');
        $dateTo = $request->query('date_to', '');

        $sports = SportsMatch::SPORTS;
        $liveCount = SportsMatch::where('state_category', 'like', '%Live%')->count();
        $topLeagues = $this->uniscore->getTopLeagues($sportKey);
        $categories = $this->uniscore->getCategories($sportKey);
        $comp = $this->uniscore->getTournamentOverview($sportKey, $compId, $seasonId, $page);

        // Optional date-range filter on competition matches
        if ($dateFrom || $dateTo) {
            $filterFn = function ($ev) use ($dateFrom, $dateTo) {
                $ts = (int) ($ev['startTimestamp'] ?? 0);
                if (!$ts) return true;
                $d = date('Y-m-d', $ts);
                if ($dateFrom && $d < $dateFrom) return false;
                if ($dateTo && $d > $dateTo) return false;
                return true;
            };
            $comp['upcoming_events'] = array_values(array_filter($comp['upcoming_events'] ?? [], $filterFn));
            $comp['finished_events'] = array_values(array_filter($comp['finished_events'] ?? [], $filterFn));
        }

        return view('uniscore.competition', compact(
            'sports', 'sportKey', 'compId', 'tab', 'liveCount',
            'topLeagues', 'categories', 'comp', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Team Profile Page (Overview, Squad, Fixtures/Results, Transfers).
     */
    public function team(Request $request, string $sportKey, string $teamId)
    {
        if (!array_key_exists($sportKey, SportsMatch::SPORTS)) {
            $sportKey = 'football';
        }
        $tab = $request->query('tab', 'overview');
        $page = max(1, (int) $request->query('page', 1));
        $sports = SportsMatch::SPORTS;
        $liveCount = SportsMatch::where('state_category', 'like', '%Live%')->count();
        $teamData = $this->uniscore->getTeamOverview($sportKey, $teamId, $page);

        return view('uniscore.team', compact('sports', 'sportKey', 'teamId', 'tab', 'liveCount', 'teamData'));
    }

    /**
     * Player Profile Page (Bio, Transfer History, Recent Match Stats).
     */
    public function player(Request $request, string $sportKey, string $playerId)
    {
        if (!array_key_exists($sportKey, SportsMatch::SPORTS)) {
            $sportKey = 'football';
        }
        $page = max(1, (int) $request->query('page', 1));
        $sports = SportsMatch::SPORTS;
        $liveCount = SportsMatch::where('state_category', 'like', '%Live%')->count();
        $playerData = $this->uniscore->getPlayerOverview($sportKey, $playerId, $page);

        return view('uniscore.player', compact('sports', 'sportKey', 'playerId', 'liveCount', 'playerData'));
    }

    /**
     * AJAX: Expand Country -> List all Leagues in that Country.
     */
    public function apiLeaguesByCategory(string $sportKey, string $categoryId)
    {
        $leagues = $this->uniscore->getLeaguesByCategory($sportKey, $categoryId);
        return response()->json(['leagues' => $leagues]);
    }

    /**
     * AJAX: Real-Time Live Updates (15s polling for scores, live minutes & status).
     */
    public function apiLiveUpdates(string $sportKey)
    {
        if (!array_key_exists($sportKey, SportsMatch::SPORTS)) {
            $sportKey = 'football';
        }
        $liveMatches = $this->uniscore->syncLiveMatches($sportKey);
        return response()->json([
            'sport' => $sportKey,
            'server_time' => time(),
            'live_count' => count($liveMatches),
            'matches' => $liveMatches,
        ]);
    }

    /**
     * AJAX: Extended Match Tabs (H2H, Analytics, AI Prediction, Bracket).
     */
    public function apiMatchExtended(string $sportKey, string $eventId, Request $request)
    {
        $data = $this->uniscore->getMatchExtendedData(
            $sportKey,
            $eventId,
            $request->query('home_id'),
            $request->query('away_id'),
            $request->query('comp_id'),
            $request->query('season_id')
        );
        return response()->json($data);
    }

    /**
     * AJAX: Fetch full live match detail (events, lineups, stats) for Right Panel.
     */
    public function apiMatchDetail(string $sportKey, string $eventId, Request $request)
    {
        $homeId = $request->query('home_id');
        $awayId = $request->query('away_id');
        $detail = $this->uniscore->getMatchFullDetail($sportKey, $eventId, $homeId, $awayId);
        return response()->json($detail);
    }

    /**
     * Admin Dashboard: Full CRUD, Filters, Scraper Trigger, Tournament Priority & System Controls.
     */
    public function admin(Request $request)
    {
        $sports = SportsMatch::SPORTS;
        $fSport = $request->query('sport', 'all');
        $fStatus = $request->query('status', 'all');
        $fDate = $request->query('date', '');
        $fQuery = trim((string)$request->query('q', ''));
        $adminTab = $request->query('view', 'matches'); // 'matches' | 'tournaments'

        $query = SportsMatch::query();
        if ($fSport !== 'all' && array_key_exists($fSport, $sports)) {
            $query->where('sport_key', $fSport);
        }
        if ($fStatus === 'live') {
            $query->where('state_category', 'like', '%Live%');
        } elseif ($fStatus === 'upcoming') {
            $query->where('state_category', 'like', '%Upcoming%');
        } elseif ($fStatus === 'finished') {
            $query->where('state_category', 'like', '%Finished%');
        } elseif ($fStatus === 'special') {
            $query->where('state_category', 'like', '%Special%');
        }
        if ($fDate !== '') {
            $query->where('match_date', $fDate);
        }
        if ($fQuery !== '') {
            $query->where(function ($q) use ($fQuery) {
                $q->where('home_team', 'like', "%{$fQuery}%")
                  ->orWhere('away_team', 'like', "%{$fQuery}%")
                  ->orWhere('tournament_name', 'like', "%{$fQuery}%");
            });
        }

        $matches = $query->orderByDesc('is_pinned')
            ->orderByDesc('match_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // Distinct Tournaments summary for Tournament Priority Manager
        $tournaments = SportsMatch::selectRaw('sport_key, tournament_name, tournament_country, COUNT(*) as match_count, SUM(CASE WHEN state_category LIKE "%Live%" THEN 1 ELSE 0 END) as live_count, MAX(is_pinned) as any_pinned')
            ->groupBy('sport_key', 'tournament_name', 'tournament_country')
            ->orderByDesc('any_pinned')
            ->orderByDesc('match_count')
            ->limit(80)
            ->get();

        $stats = [
            'total' => SportsMatch::count(),
            'live' => SportsMatch::where('state_category', 'like', '%Live%')->count(),
            'finished' => SportsMatch::where('state_category', 'like', '%Finished%')->count(),
            'tournaments' => SportsMatch::distinct('tournament_name')->count('tournament_name'),
        ];

        return view('admin.index', compact(
            'matches', 'tournaments', 'sports', 'stats',
            'fSport', 'fStatus', 'fDate', 'fQuery', 'adminTab'
        ));
    }

    /**
     * System Action: Sync DB from scraper output files.
     */
    public function adminSyncDb()
    {
        Artisan::call('sports:sync');
        return redirect()->back()->with('success', 'Đã đồng bộ lại toàn bộ dữ liệu từ file output vào SQLite DB!');
    }

    /**
     * System Action: Clear Application & View Cache.
     */
    public function adminClearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        return redirect()->back()->with('success', 'Đã xóa sạch bộ nhớ Cache hệ thống!');
    }

    /**
     * System Action: Prune matches older than 7 days.
     */
    public function adminPruneOld()
    {
        $cutoff = date('Y-m-d', strtotime('-7 days'));
        $deleted = SportsMatch::where('match_date', '<', $cutoff)->delete();
        return redirect()->back()->with('success', "Đã dọn dẹp {$deleted} trận đấu cũ hơn 7 ngày (trước {$cutoff})!");
    }

    /**
     * Tournament Priority: Pin/Unpin all matches of a tournament in 1 click.
     */
    public function adminToggleTournamentPin(Request $request)
    {
        $sportKey = $request->input('sport_key');
        $tourName = $request->input('tournament_name');
        $currentlyPinned = SportsMatch::where('sport_key', $sportKey)
            ->where('tournament_name', $tourName)
            ->where('is_pinned', true)
            ->exists();

        SportsMatch::where('sport_key', $sportKey)
            ->where('tournament_name', $tourName)
            ->update(['is_pinned' => !$currentlyPinned]);

        $msg = $currentlyPinned ? "Đã bỏ ghim giải đấu {$tourName}." : "Đã ghim ưu tiên toàn bộ trận giải {$tourName} lên đầu trang chủ!";
        return redirect()->back()->with('success', $msg);
    }

    /**
     * AJAX: Return Top Leagues for a sport (used by Admin cascading Sport -> League selector).
     */
    public function apiTopLeagues(string $sportKey)
    {
        if (!array_key_exists($sportKey, SportsMatch::SPORTS)) {
            $sportKey = 'football';
        }
        return response()->json(['leagues' => $this->uniscore->getTopLeagues($sportKey)]);
    }

    /**
     * Trigger Unlimited Rate-Limited Range Scraper in Background (non-blocking).
     */
    public function triggerScraper(Request $request)
    {
        $sport = $request->input('sport', 'football');
        $league = trim((string)$request->input('league_custom', '')) ?: trim((string)$request->input('league', ''));
        $dateFrom = $request->input('date_from') ?: date('Y-m-d');
        $dateTo = $request->input('date_to') ?: $dateFrom;
        $delay = max(0.3, min(5.0, (float)$request->input('delay', 1.0)));

        $artisan = base_path('artisan');
        $logFile = storage_path('logs/admin_crawl.log');
        $cmd = sprintf(
            'nohup php %s sports:crawl-range %s %s %s --league=%s --delay=%s > %s 2>&1 &',
            escapeshellarg($artisan),
            escapeshellarg($sport),
            escapeshellarg($dateFrom),
            escapeshellarg($dateTo),
            escapeshellarg($league),
            escapeshellarg((string)$delay),
            escapeshellarg($logFile)
        );
        exec($cmd);

        return redirect()->back()->with(
            'success',
            "🚀 Đã khởi chạy cào ngầm KHÔNG GIỚI HẠN môn {$sport} (từ {$dateFrom} đến {$dateTo}, tốc độ {$delay}s/request). Theo dõi tiến trình trực tiếp bên dưới!"
        );
    }

    /**
     * AJAX: Live Background Crawl Status & Logs.
     */
    public function adminScraperStatus()
    {
        $file = storage_path('app/crawl_status.json');
        if (!file_exists($file)) {
            return response()->json(['running' => false, 'logs' => []]);
        }
        return response()->json(json_decode(file_get_contents($file), true) ?: ['running' => false, 'logs' => []]);
    }

    /**
     * AJAX/POST: Request safe stop of running background crawl.
     */
    public function adminScraperStop()
    {
        file_put_contents(storage_path('app/crawl_stop.flag'), '1');
        return response()->json(['ok' => true]);
    }

    /**
     * Toggle Pin / Priority for a match.
     */
    public function togglePin($id)
    {
        $match = SportsMatch::findOrFail($id);
        $match->is_pinned = !$match->is_pinned;
        $match->save();

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái Ghim ưu tiên.');
    }

    /**
     * Store a manually created match.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sport_key' => 'required|string',
            'tournament_name' => 'required|string',
            'home_team' => 'required|string',
            'away_team' => 'required|string',
            'match_date' => 'required|string',
            'source' => 'required|string',
            'state_category' => 'required|string',
            'home_score' => 'nullable|integer',
            'away_score' => 'nullable|integer',
        ]);

        $validated['external_id'] = 'manual_' . time();
        SportsMatch::create($validated);

        return redirect()->back()->with('success', 'Đã tạo trận đấu mới thành công!');
    }

    /**
     * Update match score, teams, tournament, status, readtime & sync payload.
     */
    public function update(Request $request, $id)
    {
        $match = SportsMatch::findOrFail($id);
        $data = $request->only([
            'sport_key', 'tournament_name', 'home_team', 'away_team',
            'home_score', 'away_score', 'state_category', 'status_description',
            'readtime', 'match_date'
        ]);
        $data['is_pinned'] = $request->boolean('is_pinned');

        // Keep payload JSON in sync with edited scores/status
        $payload = $match->payload ?? [];
        if (isset($data['home_score'])) $payload['match_timeline']['scores']['home_total'] = $data['home_score'] !== '' ? (int)$data['home_score'] : null;
        if (isset($data['away_score'])) $payload['match_timeline']['scores']['away_total'] = $data['away_score'] !== '' ? (int)$data['away_score'] : null;
        if (!empty($data['state_category'])) $payload['match_timeline']['state_category'] = $data['state_category'];
        $data['payload'] = $payload;

        $match->update($data);

        return redirect()->back()->with('success', "Đã cập nhật trận đấu {$match->home_team} vs {$match->away_team}!");
    }

    /**
     * Delete a match.
     */
    public function destroy($id)
    {
        $match = SportsMatch::findOrFail($id);
        $match->delete();

        return redirect()->back()->with('success', 'Đã xóa trận đấu khỏi cơ sở dữ liệu!');
    }

    /**
     * Save website configuration settings (site name, tagline, logo, SEO, footer, etc.).
     */
    public function adminUpdateSettings(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:80',
            'site_tagline' => 'nullable|string|max:160',
            'site_logo_url' => 'nullable|string|max:500',
            'site_favicon_url' => 'nullable|string|max:500',
            'seo_description' => 'nullable|string|max:400',
            'notice_banner' => 'nullable|string|max:500',
            'footer_text' => 'nullable|string|max:300',
            'contact_email' => 'nullable|email|max:150',
            'support_url' => 'nullable|string|max:500',
            'default_sport' => 'required|string|in:' . implode(',', array_keys(SportsMatch::SPORTS)),
        ]);

        SiteSetting::setMany($validated);

        return redirect()->to(route('admin.index', ['view' => 'settings']) . '#section-settings')
            ->with('success', 'Đã lưu cấu hình thông tin trang web thành công!');
    }
}
