<?php

namespace App\Console\Commands;

use App\Models\SportsMatch;
use App\Services\UniScoreApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CrawlRangeCommand extends Command
{
    protected $signature = 'sports:crawl-range
        {sport : Sport key (football, basketball, tennis, etc.)}
        {date_from : Start date YYYY-MM-DD}
        {date_to : End date YYYY-MM-DD}
        {--league= : Optional league name or ID filter}
        {--delay=1.0 : Delay in seconds between HTTP requests (anti-block)}';

    protected $description = 'Crawl unlimited matches for a sport, optional league, and date range with strict rate limiting';

    private string $statusFile;
    private string $stopFile;

    public function handle(UniScoreApiService $uniscore): int
    {
        $this->statusFile = storage_path('app/crawl_status.json');
        $this->stopFile = storage_path('app/crawl_stop.flag');
        @unlink($this->stopFile);

        $sport = $this->argument('sport');
        $dateFrom = $this->argument('date_from');
        $dateTo = $this->argument('date_to');
        $leagueFilter = trim((string)$this->option('league'));
        $delaySec = max(0.3, (float)$this->option('delay'));
        $delayUs = (int)round($delaySec * 1_000_000);

        if ($dateTo < $dateFrom) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // Build list of dates in range
        $dates = [];
        $cur = strtotime($dateFrom);
        $end = strtotime($dateTo);
        while ($cur !== false && $end !== false && $cur <= $end) {
            $dates[] = date('Y-m-d', $cur);
            $cur = strtotime('+1 day', $cur);
        }

        $totalDays = count($dates);
        $state = [
            'running' => true,
            'sport' => $sport,
            'league' => $leagueFilter ?: 'Tất cả giải đấu',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'delay_sec' => $delaySec,
            'current_date' => $dateFrom,
            'days_done' => 0,
            'total_days' => $totalDays,
            'total_saved' => 0,
            'total_requests' => 0,
            'started_at' => date('H:i:s'),
            'finished_at' => null,
            'logs' => [],
        ];

        $pushLog = function (string $msg) use (&$state) {
            $state['logs'][] = '[' . date('H:i:s') . '] ' . $msg;
            if (count($state['logs']) > 80) {
                array_shift($state['logs']);
            }
            file_put_contents($this->statusFile, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        };

        $pushLog("🚀 Bắt đầu cào KHÔNG GIỚI HẠN môn [{$sport}] | Giải: [{$state['league']}] | Từ {$dateFrom} đến {$dateTo} ({$totalDays} ngày) | Tốc độ: {$delaySec}s/request");

        $slug = UniScoreApiService::SPORT_SLUGS[$sport] ?? 'football';

        foreach ($dates as $dayIdx => $dStr) {
            if (file_exists($this->stopFile)) {
                $pushLog("🛑 Nhận lệnh dừng từ Admin. Đã dừng an toàn!");
                break;
            }

            $state['current_date'] = $dStr;
            $pushLog("📅 Đang cào ngày {$dStr} (" . ($dayIdx + 1) . "/{$totalDays})...");
            $daySaved = 0;
            $daySkipped = 0;

            // Pre-load IDs of already-Finished matches for this sport & date so we NEVER overwrite them!
            $finishedIds = SportsMatch::where('sport_key', $sport)
                ->where('match_date', $dStr)
                ->where('state_category', 'like', '%Finished%')
                ->pluck('external_id')
                ->flip()
                ->all();

            if ($sport === 'football') {
                // Paginate ALL pages until no more events (unlimited matches!)
                for ($page = 1; $page <= 30; $page++) {
                    if (file_exists($this->stopFile)) break;

                    $state['total_requests']++;
                    $resp = $this->fetchRaw("sport/football/scheduled-events-pagination-v2/{$dStr}/locale/VN/type/all", [
                        'sortByMatchTime' => 'false',
                        'page' => $page,
                    ]);
                    $events = $resp['data']['events'] ?? [];
                    if (empty($events)) {
                        break;
                    }

                    $pageMatched = 0;
                    $pageSkipped = 0;
                    foreach ($events as $ev) {
                        $eid = $ev['id'] ?? '';
                        if ($eid && isset($finishedIds[$eid])) {
                            $daySkipped++;
                            $pageSkipped++;
                            continue; // Skip already-Finished match in DB!
                        }
                        if ($leagueFilter !== '') {
                            $tId = strtolower((string)($ev['tournament']['id'] ?? ''));
                            $tName = strtolower((string)($ev['tournament']['name'] ?? ''));
                            $lf = strtolower($leagueFilter);
                            if ($tId !== $lf && !str_contains($tName, $lf)) {
                                continue;
                            }
                        }
                        if ($uniscore->upsertFootballEvent($ev, 'football', $dStr)) {
                            $daySaved++;
                            $pageMatched++;
                            $state['total_saved']++;
                        }
                    }

                    $pushLog("   ↳ Trang {$page}: quét " . count($events) . " trận, lưu mới/cập nhật {$pageMatched}, bỏ qua {$pageSkipped} trận đã Finished.");

                    // Anti-block rate limit delay between requests
                    usleep($delayUs);
                    if (count($events) < 15) {
                        break; // Last page reached
                    }
                }
            } else {
                // Compact format for the other 9 sports — parse ALL matches (no artificial 80-match cap!)
                $state['total_requests']++;
                $resp = $this->fetchRaw("sport/{$slug}/scheduled-events/{$dStr}/offset/-420");
                $rawStr = $resp['data']['events'] ?? '';
                if (is_string($rawStr) && $rawStr !== '') {
                    $rows = explode('!!', $rawStr);
                    $refMethod = new \ReflectionMethod(UniScoreApiService::class, 'upsertCompactEvent');
                    $refMethod->setAccessible(true);

                    foreach ($rows as $row) {
                        $eid = explode('^', $row, 2)[0] ?? '';
                        if ($eid && isset($finishedIds[$eid])) {
                            $daySkipped++;
                            continue; // Skip already-Finished match in DB!
                        }
                        if ($leagueFilter !== '' && !str_contains(strtolower($row), strtolower($leagueFilter))) {
                            continue;
                        }
                        if ($refMethod->invoke($uniscore, $row, $sport, $dStr, false)) {
                            $daySaved++;
                            $state['total_saved']++;
                        }
                    }
                    $pushLog("   ↳ Quét " . count($rows) . " trận thô, lưu mới/cập nhật {$daySaved}, bỏ qua {$daySkipped} trận đã Finished.");
                } else {
                    $pushLog("   ↳ Không có trận đấu nào trong ngày {$dStr}.");
                }
                usleep($delayUs);
            }

            $state['days_done'] = $dayIdx + 1;
            $pushLog("✅ Xong ngày {$dStr}: lưu {$daySaved} • bỏ qua {$daySkipped} đã có (Tổng đã lưu: {$state['total_saved']}).");
        }

        $state['running'] = false;
        $state['finished_at'] = date('H:i:s');
        $pushLog("🎉 HOÀN TẤT ĐỢT CÀO! Tổng đã lưu: {$state['total_saved']} trận | Tổng request: {$state['total_requests']}.");
        @unlink($this->stopFile);

        return Command::SUCCESS;
    }

    private function fetchRaw(string $endpoint, array $params = []): ?array
    {
        try {
            $resp = Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Origin' => 'https://uniscore.com',
                    'Referer' => 'https://uniscore.com/',
                ])
                ->get('https://api.uni-score.com/api/v2/' . ltrim($endpoint, '/'), array_merge(['language' => 'vi'], $params));
            return $resp->successful() ? $resp->json() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
