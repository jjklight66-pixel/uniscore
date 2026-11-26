<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $comp['info']['name'] ?? 'Chi tiết giải đấu' }} - UNISCORE</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <style>
    :root { color-scheme: dark; }
    body {
      background-color: #0b0d12;
      color: #d1d5db;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }
    select, input {
      background-color: #0f1219 !important;
      color: #f3f4f6 !important;
      border-color: #2a3040 !important;
      color-scheme: dark;
    }
    select option { background-color: #141821; color: #f3f4f6; }
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: #0b0d12; }
    ::-webkit-scrollbar-thumb { background: #2a3040; border-radius: 4px; }
  </style>
</head>
<body class="min-h-screen flex flex-col">

  <!-- TOP HEADER -->
  <header class="bg-[#12151e] border-b border-[#232836] px-4 lg:px-6 h-14 flex items-center justify-between sticky top-0 z-50">
    <div class="flex items-center gap-5">
      <a href="{{ route('home', ['sport' => $sportKey]) }}" class="flex items-center gap-1 text-xl font-black tracking-tight">
        <span class="text-[#3b82f6]">UNI</span><span class="text-[#f59e0b]">SCORE</span>
      </a>
      <div class="hidden sm:flex items-center gap-2 text-xs text-gray-400">
        <a href="{{ route('home', ['sport' => $sportKey]) }}" class="hover:text-white">{{ $sports[$sportKey]['name'] ?? 'Thể thao' }}</a>
        <span>›</span>
        <span class="text-white font-semibold">{{ $comp['info']['name'] ?? 'Giải đấu' }}</span>
      </div>
    </div>

    <div class="flex items-center gap-3 text-xs font-semibold">
      <a href="{{ route('home', ['sport' => $sportKey]) }}" class="bg-[#1b1f2e] hover:bg-[#252b3d] text-gray-200 border border-[#2a3040] px-3 py-1.5 rounded-lg transition">
        ← Trang chủ
      </a>
      <a href="{{ route('admin.index') }}" class="bg-[#1b1f2e] hover:bg-[#252b3d] text-amber-300 border border-amber-800/50 px-3 py-1.5 rounded-lg transition">
        🛠️ Admin
      </a>
    </div>
  </header>

  @php
    $info = $comp['info'] ?? [];
    $compName = $info['name'] ?? 'Giải đấu';
    $countryName = $info['category']['name'] ?? ($info['country']['name'] ?? 'Quốc tế');
    $seasons = $comp['seasons'] ?? [];
    $activeSeason = $comp['active_season'] ?? '';
    $activePage = $comp['active_page'] ?? 1;
    $standingsGroups = $comp['standings'] ?? [];
    $upcomingEvents = $comp['upcoming_events'] ?? [];
    $finishedEvents = $comp['finished_events'] ?? [];
    $transfers = $comp['transfers'] ?? [];
    $topPlayers = $comp['top_players'] ?? [];
    $topTeams = $comp['top_teams'] ?? [];
    $overviewStats = $comp['overview_stats'] ?? [];
    $champions = $comp['champions'] ?? [];
  @endphp

  <!-- MAIN CONTAINER -->
  <div class="flex-1 max-w-[1440px] w-full mx-auto px-3 lg:px-6 py-5 flex gap-4 items-start">

    <!-- LEFT SIDEBAR: TOP LEAGUES -->
    <aside class="hidden lg:flex flex-col w-[240px] shrink-0 bg-[#141720] border border-[#232836] rounded-xl p-3 sticky top-[72px] max-h-[calc(100vh-90px)] overflow-y-auto">
      <h2 class="text-xs font-bold text-white pb-2 mb-2 border-b border-[#232836]">Giải Đấu Nổi Bật</h2>
      <div class="space-y-1">
        @foreach($topLeagues as $lg)
          <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $lg['id']]) }}"
             class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg transition {{ $lg['id'] === $compId ? 'bg-[#1f2638] text-[#f59e0b] font-bold border border-[#f59e0b]/40' : 'text-gray-300 hover:bg-[#1e2330]' }}">
            <img src="{{ $lg['logo'] }}" class="w-5 h-5 object-contain rounded-sm bg-[#12151e] p-0.5" onerror="this.style.display='none'">
            <span class="text-xs truncate">{{ $lg['name'] }}</span>
          </a>
        @endforeach
      </div>
    </aside>

    <!-- MAIN COMPETITION CONTENT -->
    <main class="flex-1 min-w-0 space-y-4">

      <!-- TOURNAMENT BANNER HEADER -->
      <div class="bg-gradient-to-r from-[#241c14] via-[#1a1818] to-[#141720] border border-[#2e2a25] rounded-2xl p-5 shadow-xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <img src="{{ $comp['logo'] }}" alt="{{ $compName }}"
                 class="w-16 h-16 object-contain rounded-xl bg-[#0f1219] p-2 border border-[#2a3040] shadow"
                 onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($compName,0,2)) }}&background=1e2330&color=f59e0b&size=64'">
            <div>
              <h1 class="text-2xl font-black text-white">{{ $compName }}</h1>
              <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-300">
                <span>{{ $countryName }}</span>
                @if(!empty($seasons))
                  <form action="{{ route('competition.show', ['sport' => $sportKey, 'id' => $compId]) }}" method="GET" class="inline-block">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <select name="season" onchange="this.form.submit()" class="px-2.5 py-1 rounded-full text-xs border border-[#3a3f4e]">
                      @foreach(array_slice($seasons, 0, 12) as $s)
                        <option value="{{ $s['id'] }}" {{ $s['id'] === $activeSeason ? 'selected' : '' }}>
                          {{ $s['year'] ?? $s['name'] ?? $s['id'] }}
                        </option>
                      @endforeach
                    </select>
                  </form>
                @endif
              </div>
            </div>
          </div>
          <div class="text-2xl text-gray-500 hover:text-[#f59e0b] cursor-pointer" title="Theo dõi giải đấu">☆</div>
        </div>

        <!-- ALL 8 UNISCORE COMPETITION TABS -->
        <div class="flex flex-wrap items-center gap-2 mt-5 pt-4 border-t border-white/10 text-xs font-semibold">
          @php
            $compTabs = [
              'overview' => '☰ Tổng Quan',
              'matches' => '📅 Danh Sách Trận Đấu',
              'standings' => '📊 Bảng Xếp Hạng',
              'bracket' => '🌳 Nhánh Đấu',
              'transfers' => '⇄ Chuyển Nhượng',
              'players' => '⭐ Cầu Thủ Nổi Bật',
              'teams' => '🏆 Đội Bóng Hàng Đầu',
              'stats' => '📈 Thống Kê Hàng Đầu',
              'cup' => '🏅 Cúp',
            ];
          @endphp
          @foreach($compTabs as $tKey => $tLabel)
            <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $compId, 'season' => $activeSeason, 'tab' => $tKey]) }}"
               class="px-3 py-1.5 rounded-full border transition {{ $tab === $tKey ? 'bg-[#2a2215] text-[#f59e0b] border-[#f59e0b]/60' : 'bg-[#141720]/80 text-gray-300 border-[#2a3040] hover:text-white' }}">
              {{ $tLabel }}
            </a>
          @endforeach
        </div>
      </div>

      <!-- =================================================================== -->
      <!-- TAB: TỔNG QUAN / DANH SÁCH TRẬN ĐẤU (UPCOMING + FINISHED + RANGE)   -->
      <!-- =================================================================== -->
      @if($tab === 'overview' || $tab === 'matches')
        <!-- Date Range Filter Bar for Matches -->
        <div class="bg-[#141720] border border-[#232836] rounded-xl p-3 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="text-gray-300 font-semibold">
            📅 Lọc trận đấu theo khoảng ngày ({{ count($upcomingEvents) }} sắp đấu • {{ count($finishedEvents) }} đã kết thúc)
          </div>
          <form id="compDateRangeForm" action="{{ route('competition.show', ['sport' => $sportKey, 'id' => $compId]) }}" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="season" value="{{ $activeSeason }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" id="compDateFrom" name="date_from" value="{{ $dateFrom }}">
            <input type="hidden" id="compDateTo" name="date_to" value="{{ $dateTo }}">
            <input type="text" id="compRangePicker" readonly
                   value="{{ ($dateFrom && $dateTo) ? ($dateFrom . ' to ' . $dateTo) : '' }}"
                   placeholder="Chọn từ ngày - đến ngày..."
                   class="px-3 py-1.5 w-52 rounded-lg text-xs border border-[#2a3040] cursor-pointer">
            <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#f59e0b] text-black font-bold">Lọc</button>
            @if($dateFrom || $dateTo)
              <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $compId, 'season' => $activeSeason, 'tab' => $tab]) }}"
                 class="px-2.5 py-1.5 rounded-lg bg-[#1b1f2e] text-gray-300">Xóa lọc</a>
            @endif
          </form>
        </div>

        <!-- UPCOMING MATCHES -->
        @if(!empty($upcomingEvents))
          <div class="bg-[#141720] border border-[#232836] rounded-xl overflow-hidden">
            <div class="bg-[#191d28] px-4 py-2.5 border-b border-[#232836] flex justify-between items-center">
              <h2 class="text-xs font-bold text-white">⏳ Trận đấu sắp tới</h2>
              <span class="text-[10px] text-gray-400">{{ count($upcomingEvents) }} trận</span>
            </div>
            <div class="divide-y divide-[#1f2433]">
              @foreach(($tab === 'overview' ? array_slice($upcomingEvents, 0, 8) : $upcomingEvents) as $ev)
                @php
                  $hTeam = $ev['homeTeam'] ?? [];
                  $aTeam = $ev['awayTeam'] ?? [];
                  $hLogo = \App\Services\UniScoreApiService::teamLogo($sportKey, $hTeam['id'] ?? '');
                  $aLogo = \App\Services\UniScoreApiService::teamLogo($sportKey, $aTeam['id'] ?? '');
                  $ts = (int)($ev['startTimestamp'] ?? 0);
                @endphp
                <div class="px-4 py-2.5 hover:bg-[#1c2130] flex items-center justify-between gap-3">
                  <div class="w-24 text-[11px] text-gray-400 font-mono">{{ $ts ? date('d/m/Y', $ts) : '' }}</div>
                  <div class="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                    <div class="flex items-center justify-end gap-2">
                      <span class="text-xs font-semibold text-white text-right">{{ $hTeam['name'] ?? 'Home' }}</span>
                      <img src="{{ $hLogo }}" class="w-5 h-5 object-contain" onerror="this.style.display='none'">
                    </div>
                    <div class="px-3 py-1 rounded bg-[#0f1219] border border-[#2a3040] text-xs font-bold text-blue-400 min-w-[58px] text-center">
                      {{ $ts ? date('H:i', $ts) : 'vs' }}
                    </div>
                    <div class="flex items-center justify-start gap-2">
                      <img src="{{ $aLogo }}" class="w-5 h-5 object-contain" onerror="this.style.display='none'">
                      <span class="text-xs font-semibold text-white">{{ $aTeam['name'] ?? 'Away' }}</span>
                    </div>
                  </div>
                  <div class="w-16 text-right text-[10px] text-gray-500">Sắp đấu</div>
                </div>
              @endforeach
            </div>
          </div>
        @endif

        <!-- FINISHED / RECENT MATCHES -->
        <div class="bg-[#141720] border border-[#232836] rounded-xl overflow-hidden">
          <div class="bg-[#191d28] px-4 py-2.5 border-b border-[#232836] flex justify-between items-center">
            <h2 class="text-xs font-bold text-white">✅ Trận đấu gần nhất / Kết quả (Trang {{ $activePage }})</h2>
            <div class="flex items-center gap-2 text-xs">
              @if($activePage > 1)
                <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $compId, 'season' => $activeSeason, 'tab' => $tab, 'page' => $activePage - 1]) }}"
                   class="px-2 py-0.5 rounded bg-[#0f1219] border border-[#2a3040] text-gray-300 hover:border-[#f59e0b]">‹ Trang trước</a>
              @endif
              <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $compId, 'season' => $activeSeason, 'tab' => $tab, 'page' => $activePage + 1]) }}"
                 class="px-2 py-0.5 rounded bg-[#0f1219] border border-[#2a3040] text-gray-300 hover:border-[#f59e0b]">Trang sau ›</a>
            </div>
          </div>
          <div class="divide-y divide-[#1f2433]">
            @forelse(($tab === 'overview' ? array_slice($finishedEvents, 0, 10) : $finishedEvents) as $ev)
              @php
                $hTeam = $ev['homeTeam'] ?? [];
                $aTeam = $ev['awayTeam'] ?? [];
                $hLogo = \App\Services\UniScoreApiService::teamLogo($sportKey, $hTeam['id'] ?? '');
                $aLogo = \App\Services\UniScoreApiService::teamLogo($sportKey, $aTeam['id'] ?? '');
                $hScore = $ev['homeScore']['display'] ?? ($ev['homeScore']['current'] ?? '-');
                $aScore = $ev['awayScore']['display'] ?? ($ev['awayScore']['current'] ?? '-');
                $ts = (int)($ev['startTimestamp'] ?? 0);
              @endphp
              <div class="px-4 py-2.5 hover:bg-[#1c2130] flex items-center justify-between gap-3">
                <div class="w-24 text-[11px] text-gray-400 font-mono">{{ $ts ? date('d/m/Y', $ts) : 'FT' }}</div>
                <div class="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                  <div class="flex items-center justify-end gap-2">
                    <span class="text-xs font-semibold text-white text-right">{{ $hTeam['name'] ?? 'Home' }}</span>
                    <img src="{{ $hLogo }}" class="w-5 h-5 object-contain" onerror="this.style.display='none'">
                  </div>
                  <div class="px-3 py-1 rounded bg-[#0f1219] border border-[#2a3040] text-xs font-black text-[#f59e0b] min-w-[58px] text-center">
                    {{ $hScore }} - {{ $aScore }}
                  </div>
                  <div class="flex items-center justify-start gap-2">
                    <img src="{{ $aLogo }}" class="w-5 h-5 object-contain" onerror="this.style.display='none'">
                    <span class="text-xs font-semibold text-white">{{ $aTeam['name'] ?? 'Away' }}</span>
                  </div>
                </div>
                <div class="w-16 text-right text-[10px] text-gray-500">FT</div>
              </div>
            @empty
              <div class="p-8 text-center text-xs text-gray-500">Không có trận đấu nào trong khoảng thời gian đã chọn.</div>
            @endforelse
          </div>
        </div>
      @endif

      <!-- =================================================================== -->
      <!-- TAB: BẢNG XẾP HẠNG (STANDINGS)                                      -->
      <!-- =================================================================== -->
      @if($tab === 'standings' || $tab === 'overview')
        <div class="bg-[#141720] border border-[#232836] rounded-xl overflow-hidden">
          <div class="bg-[#191d28] px-4 py-3 border-b border-[#232836]">
            <h2 class="text-xs font-bold text-white">📊 Bảng Xếp Hạng (Standings)</h2>
          </div>
          @forelse($standingsGroups as $stGroup)
            @php
              $rows = $stGroup['rows'] ?? [];
              $groupTitle = ltrim((string)($stGroup['name'] ?? ''), '_');
              $isBkb = ($sportKey === 'basketball');
            @endphp
            @if(!empty($rows))
              @if($groupTitle)
                <div class="bg-[#151923] px-4 py-2 text-[11px] font-bold text-[#f59e0b] border-t border-[#232836] uppercase tracking-wide">
                  {{ $groupTitle }}
                </div>
              @endif
              <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                  <thead class="bg-[#10131b] text-gray-400 border-b border-[#232836]">
                    <tr>
                      <th class="py-2.5 px-3 w-10 text-center">#</th>
                      <th class="py-2.5 px-3">Đội bóng</th>
                      <th class="py-2.5 px-2 text-center">Trận</th>
                      <th class="py-2.5 px-2 text-center">T</th>
                      @if(!$isBkb)<th class="py-2.5 px-2 text-center">H</th>@endif
                      <th class="py-2.5 px-2 text-center">B</th>
                      @if(!$isBkb)
                        <th class="py-2.5 px-2 text-center">BT:BB</th>
                        <th class="py-2.5 px-2 text-center">HS</th>
                        <th class="py-2.5 px-3 text-center font-bold text-white">Điểm</th>
                      @else
                        <th class="py-2.5 px-3 text-center font-bold text-white">Tỷ lệ (PCT)</th>
                      @endif
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-[#1f2433]">
                    @foreach($rows as $row)
                      @php
                        $tm = $row['team'] ?? [];
                        $tmLogo = \App\Services\UniScoreApiService::teamLogo($sportKey, $tm['id'] ?? '');
                        $pos = $row['position'] ?? '-';
                        $mCount = (int)($row['matches'] ?? 0);
                        $wCount = (int)($row['wins'] ?? 0);
                        $gf = $row['scoresFor'] ?? 0;
                        $ga = $row['scoresAgainst'] ?? 0;
                        $diff = $gf - $ga;
                        $pct = $mCount > 0 ? number_format($wCount / $mCount, 3) : '.000';
                      @endphp
                      <tr class="hover:bg-[#1c2130]">
                        <td class="py-2 px-3 text-center font-bold {{ $pos <= 4 ? 'text-blue-400' : ($pos >= count($rows) - 2 ? 'text-red-400' : 'text-gray-400') }}">{{ $pos }}</td>
                        <td class="py-2 px-3">
                          <div class="flex items-center gap-2.5">
                            <img src="{{ $tmLogo }}" class="w-5 h-5 object-contain" onerror="this.style.display='none'">
                            <span class="font-semibold text-white">{{ $tm['name'] ?? 'Team' }}</span>
                          </div>
                        </td>
                        <td class="py-2 px-2 text-center text-gray-300">{{ $mCount }}</td>
                        <td class="py-2 px-2 text-center text-emerald-400">{{ $wCount }}</td>
                        @if(!$isBkb)<td class="py-2 px-2 text-center text-gray-400">{{ $row['draws'] ?? 0 }}</td>@endif
                        <td class="py-2 px-2 text-center text-red-400">{{ $row['losses'] ?? 0 }}</td>
                        @if(!$isBkb)
                          <td class="py-2 px-2 text-center text-gray-300">{{ $gf }}:{{ $ga }}</td>
                          <td class="py-2 px-2 text-center {{ $diff > 0 ? 'text-emerald-400' : ($diff < 0 ? 'text-red-400' : 'text-gray-400') }}">{{ $diff > 0 ? '+' . $diff : $diff }}</td>
                          <td class="py-2 px-3 text-center font-black text-[#f59e0b]">{{ $row['points'] ?? 0 }}</td>
                        @else
                          <td class="py-2 px-3 text-center font-black text-[#f59e0b]">{{ $pct }}</td>
                        @endif
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          @empty
            <div class="p-6 text-center text-xs text-gray-500">Chưa có bảng xếp hạng cho mùa giải này.</div>
          @endforelse
        </div>
      @endif

      <!-- =================================================================== -->
      <!-- TAB: NHÁNH ĐẤU (BRACKET / KNOCKOUT TREE)                            -->
      <!-- =================================================================== -->
      @if($tab === 'bracket')
        @php
          $bracketData = $comp['bracket'] ?? null;
          $bracketRounds = is_array($bracketData) ? ($bracketData['rounds'] ?? (isset($bracketData[0]['blocks']) ? $bracketData : [])) : [];
        @endphp
        <div class="bg-[#141720] border border-[#232836] rounded-xl p-5">
          <h2 class="text-sm font-bold text-white mb-4">🌳 Nhánh Đấu Loại Trực Tiếp (Knockout Bracket)</h2>
          @if(!empty($bracketRounds))
            <div class="flex gap-4 overflow-x-auto pb-3">
              @foreach($bracketRounds as $brRound)
                <div class="min-w-[230px] shrink-0 space-y-3">
                  <div class="text-xs font-bold uppercase text-[#f59e0b] bg-[#191d28] px-3 py-2 rounded-lg border border-[#232836] text-center">
                    {{ $brRound['description'] ?? ($brRound['name'] ?? 'Vòng loại trực tiếp') }}
                  </div>
                  <div class="space-y-2.5">
                    @foreach(($brRound['blocks'] ?? ($brRound['matches'] ?? [])) as $blk)
                      @php
                        $p1 = $blk['participants'][0] ?? [];
                        $p2 = $blk['participants'][1] ?? [];
                        $t1Logo = \App\Services\UniScoreApiService::teamLogo($sportKey, $p1['team']['id'] ?? '');
                        $t2Logo = \App\Services\UniScoreApiService::teamLogo($sportKey, $p2['team']['id'] ?? '');
                      @endphp
                      <div class="bg-[#0f1219] border border-[#232836] rounded-lg p-3 space-y-2 text-xs">
                        <div class="flex justify-between items-center gap-2">
                          <div class="flex items-center gap-2 min-w-0">
                            <img src="{{ $t1Logo }}" class="w-4 h-4 object-contain shrink-0" onerror="this.style.display='none'">
                            <span class="truncate {{ !empty($p1['winner']) ? 'text-white font-bold' : 'text-gray-400' }}">{{ $p1['team']['name'] ?? 'Chờ xác định' }}</span>
                          </div>
                          <span class="font-mono font-bold {{ !empty($p1['winner']) ? 'text-[#f59e0b]' : 'text-gray-400' }}">{{ $blk['homeTeamScore'] ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                          <div class="flex items-center gap-2 min-w-0">
                            <img src="{{ $t2Logo }}" class="w-4 h-4 object-contain shrink-0" onerror="this.style.display='none'">
                            <span class="truncate {{ !empty($p2['winner']) ? 'text-white font-bold' : 'text-gray-400' }}">{{ $p2['team']['name'] ?? 'Chờ xác định' }}</span>
                          </div>
                          <span class="font-mono font-bold {{ !empty($p2['winner']) ? 'text-[#f59e0b]' : 'text-gray-400' }}">{{ $blk['awayTeamScore'] ?? '-' }}</span>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="p-8 text-center text-xs text-gray-500">
              Giải đấu / mùa giải này hiện thi đấu theo thể thức bảng tính điểm (chưa mở Nhánh đấu loại trực tiếp).
            </div>
          @endif
        </div>
      @endif

      <!-- =================================================================== -->
      <!-- TAB: CHUYỂN NHƯỢNG (TRANSFERS — 100% EXACT UNISCORE DETAIL!)        -->
      <!-- =================================================================== -->
      @if($tab === 'transfers')
        @php
          $trTypeMap = [
            'loan' => 'Cho mượn',
            'end_of_loan' => 'Kết thúc mượn',
            'free' => 'Chuyển nhượng tự do',
            'free_transfer' => 'Chuyển nhượng tự do',
            'transfer' => 'Chuyển nhượng',
            'unknown' => 'Chuyển nhượng',
          ];
        @endphp

        <!-- Filter Bar (matching Screenshot 3: Tất cả | ← Gia nhập | Rời đội → + Club Dropdown) -->
        <div class="bg-[#141720] border border-[#232836] rounded-xl p-3.5 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="flex items-center gap-2">
            <button type="button" onclick="filterTransfers('all', this)" class="tr-dir-btn px-3.5 py-1.5 rounded-full bg-blue-600 text-white font-bold transition">
              Tất cả
            </button>
            <button type="button" onclick="filterTransfers('in', this)" class="tr-dir-btn px-3.5 py-1.5 rounded-full bg-[#1b1f2c] text-emerald-400 hover:bg-[#252b3d] font-semibold transition">
              ← Gia nhập
            </button>
            <button type="button" onclick="filterTransfers('out', this)" class="tr-dir-btn px-3.5 py-1.5 rounded-full bg-[#1b1f2c] text-red-400 hover:bg-[#252b3d] font-semibold transition">
              Rời đội →
            </button>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-gray-400">CLB:</span>
            <select id="trClubFilter" onchange="applyTransferFilters()" class="px-3 py-1.5 rounded-lg text-xs border border-[#2a3040]">
              <option value="all">Tất cả CLB ({{ count($transfers) }})</option>
              @foreach($transfers as $tm)
                <option value="{{ $tm['team_id'] ?? '' }}">{{ $tm['name'] ?? 'CLB' }} ({{ count($tm['players'] ?? []) }})</option>
              @endforeach
            </select>
          </div>
        </div>

        <div id="transfersListContainer" class="space-y-4">
          @forelse($transfers as $teamTr)
            @php
              $clubId = $teamTr['team_id'] ?? '';
              $clubName = $teamTr['name'] ?? 'CLB';
              $clubLogo = \App\Services\UniScoreApiService::teamLogo($sportKey, $clubId);
              $players = $teamTr['players'] ?? [];
            @endphp
            @if(!empty($players))
              <div class="transfer-club-card bg-[#141720] border border-[#232836] rounded-xl overflow-hidden" data-club-id="{{ $clubId }}">
                <!-- Club Header -->
                <div class="bg-[#191d28] px-4 py-2.5 border-b border-[#232836] flex items-center justify-between">
                  <div class="flex items-center gap-2.5">
                    <img src="{{ $clubLogo }}" class="w-5 h-5 object-contain" onerror="this.style.display='none'">
                    <h3 class="text-xs font-bold text-white">{{ $clubName }}</h3>
                  </div>
                  <span class="text-[10px] text-gray-400">{{ count($players) }} thương vụ</span>
                </div>

                <!-- Transfer Rows (matching Screenshot 3) -->
                <div class="divide-y divide-[#1f2433]">
                  @foreach($players as $item)
                    @php
                      $pl = $item['player'] ?? [];
                      $pId = $pl['id'] ?? '';
                      $pName = $pl['name'] ?? ($pl['shortName'] ?? 'Cầu thủ');
                      $pPos = $pl['position'] ?? '';
                      $pAvatar = \App\Services\UniScoreApiService::playerAvatar($sportKey, $pId);

                      $dir = strtolower($item['direction'] ?? 'out');
                      $isIn = ($dir === 'in');

                      // Opposing club: if "in", show transferFrom; if "out", show transferTo
                      $otherTeam = $isIn ? ($item['transferFrom'] ?? []) : ($item['transferTo'] ?? []);
                      $otherId = $otherTeam['id'] ?? '';
                      $otherName = $otherTeam['name'] ?? 'Chưa rõ CLB';
                      $otherLogo = \App\Services\UniScoreApiService::teamLogo($sportKey, $otherId);

                      // Transfer fee & type description
                      $feeKey = strtolower($item['transferFeeDescriptionKey'] ?? '');
                      $feeDesc = $trTypeMap[$feeKey] ?? ($item['transferFeeDescription'] ?? 'Chuyển nhượng');
                      $rawFeeVal = $item['transferFeeRaw']['value'] ?? ($item['transferFee'] ?? 0);
                      $feeAmountStr = '';
                      if (is_numeric($rawFeeVal) && $rawFeeVal > 0) {
                          if ($rawFeeVal >= 1000000) {
                              $feeAmountStr = '€' . round($rawFeeVal / 1000000, 1) . 'M';
                          } elseif ($rawFeeVal >= 1000) {
                              $feeAmountStr = '€' . round($rawFeeVal / 1000) . 'K';
                          } else {
                              $feeAmountStr = '€' . $rawFeeVal;
                          }
                      }

                      $trTs = (int)($item['transferDateTimestamp'] ?? 0);
                      $trDateLabel = $trTs > 0 ? date('d', $trTs) . ' thg ' . (int)date('m', $trTs) . ', ' . date('Y', $trTs) : '';
                    @endphp
                    <div class="transfer-row px-4 py-2.5 grid grid-cols-[1fr_auto_1fr] items-center gap-3 hover:bg-[#1c2130] transition text-xs"
                         data-direction="{{ $dir }}">

                      <!-- Left: Player Avatar + Real Name + Position + Transfer Type/Fee -->
                      <div class="flex items-center gap-3 min-w-0">
                        <img src="{{ $pAvatar }}" alt="{{ $pName }}"
                             class="w-9 h-9 rounded-full object-cover bg-[#0f1219] border border-[#2a3040] shrink-0"
                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($pName,0,2)) }}&background=1e2330&color=fff&size=40'">
                        <div class="min-w-0">
                          <div class="flex items-center gap-1.5">
                            <span class="font-bold text-white truncate">{{ $pName }}</span>
                            @if($pPos)
                              <span class="text-[9px] px-1 py-0.2 rounded bg-[#1e2330] text-gray-400 font-mono">{{ $pPos }}</span>
                            @endif
                          </div>
                          <div class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-0.5">
                            <span>{{ $feeDesc }}</span>
                            @if($feeAmountStr)
                              <span class="text-emerald-400 font-bold">{{ $feeAmountStr }}</span>
                            @endif
                          </div>
                        </div>
                      </div>

                      <!-- Center: Green ← (Gia nhập) or Red → (Rời đội) -->
                      <div class="px-3 text-center">
                        @if($isIn)
                          <span class="text-emerald-400 font-black text-base" title="Gia nhập {{ $clubName }}">←</span>
                        @else
                          <span class="text-red-400 font-black text-base" title="Rời {{ $clubName }}">→</span>
                        @endif
                      </div>

                      <!-- Right: Opposing Club Logo + Club Name + Transfer Date -->
                      <div class="flex items-center justify-end gap-2.5 min-w-0 text-right">
                        <div class="min-w-0">
                          <div class="text-xs font-semibold text-gray-200 truncate">{{ $otherName }}</div>
                          @if($trDateLabel)
                            <div class="text-[10px] text-gray-500">{{ $trDateLabel }}</div>
                          @endif
                        </div>
                        <img src="{{ $otherLogo }}" alt="{{ $otherName }}"
                             class="w-6 h-6 object-contain shrink-0 rounded-sm bg-[#0f1219] p-0.5"
                             onerror="this.style.display='none'">
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @endif
          @empty
            <div class="bg-[#141720] border border-[#232836] rounded-xl p-10 text-center text-xs text-gray-500">
              Chưa có dữ liệu chuyển nhượng cho mùa giải này.
            </div>
          @endforelse
        </div>

        <script>
          let activeTransferDir = 'all';
          function filterTransfers(dir, btn) {
            activeTransferDir = dir;
            document.querySelectorAll('.tr-dir-btn').forEach(b => {
              b.className = 'tr-dir-btn px-3.5 py-1.5 rounded-full bg-[#1b1f2c] text-gray-300 hover:bg-[#252b3d] font-semibold transition';
            });
            if (dir === 'in') btn.className = 'tr-dir-btn px-3.5 py-1.5 rounded-full bg-emerald-600 text-white font-bold transition';
            else if (dir === 'out') btn.className = 'tr-dir-btn px-3.5 py-1.5 rounded-full bg-red-600 text-white font-bold transition';
            else btn.className = 'tr-dir-btn px-3.5 py-1.5 rounded-full bg-blue-600 text-white font-bold transition';
            applyTransferFilters();
          }

          function applyTransferFilters() {
            const clubId = document.getElementById('trClubFilter').value;
            document.querySelectorAll('.transfer-club-card').forEach(card => {
              const matchClub = (clubId === 'all' || card.dataset.clubId === clubId);
              if (!matchClub) { card.style.display = 'none'; return; }
              let visibleRows = 0;
              card.querySelectorAll('.transfer-row').forEach(row => {
                const matchDir = (activeTransferDir === 'all' || row.dataset.direction === activeTransferDir);
                row.style.display = matchDir ? '' : 'none';
                if (matchDir) visibleRows++;
              });
              card.style.display = visibleRows > 0 ? '' : 'none';
            });
          }
        </script>
      @endif

      <!-- =================================================================== -->
      <!-- TAB: CẦU THỦ NỔI BẬT (TOP PLAYERS)                                  -->
      <!-- =================================================================== -->
      @if($tab === 'players')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @php
            $playerCats = [
              'goals' => ['title' => '⚽ Vua Phá Lưới (Ghi bàn)', 'key' => 'goals'],
              'assists' => ['title' => '🎯 Vua Kiến Tạo (Assists)', 'key' => 'assists'],
              'rating' => ['title' => '⭐ Điểm Đánh Giá Cao Nhất', 'key' => 'rating'],
            ];
          @endphp
          @foreach($playerCats as $catKey => $catMeta)
            @php $plist = $topPlayers[$catKey] ?? []; @endphp
            <div class="bg-[#141720] border border-[#232836] rounded-xl overflow-hidden">
              <div class="bg-[#191d28] px-4 py-2.5 border-b border-[#232836] text-xs font-bold text-white">{{ $catMeta['title'] }}</div>
              <div class="divide-y divide-[#1f2433]">
                @forelse(array_slice($plist, 0, 10) as $idx => $item)
                  @php
                    $pl = $item['player'] ?? [];
                    $tm = $item['team'] ?? [];
                    $statVal = $item['statistics'][$catMeta['key']] ?? '-';
                    $avatar = \App\Services\UniScoreApiService::playerAvatar($sportKey, $pl['id'] ?? '');
                  @endphp
                  <div class="px-4 py-2.5 flex items-center justify-between text-xs hover:bg-[#1c2130]">
                    <div class="flex items-center gap-3">
                      <span class="w-5 text-center font-bold text-gray-500">{{ $idx + 1 }}</span>
                      <img src="{{ $avatar }}" class="w-8 h-8 rounded-full object-cover bg-[#0f1219]"
                           onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($pl['name'] ?? 'P',0,2)) }}&background=1e2330&color=fff&size=32'">
                      <div>
                        <div class="font-bold text-white">{{ $pl['name'] ?? 'Player' }}</div>
                        <div class="text-[10px] text-gray-400">{{ $tm['name'] ?? '' }}</div>
                      </div>
                    </div>
                    <span class="text-sm font-black text-[#f59e0b]">{{ is_numeric($statVal) && $catKey === 'rating' ? number_format($statVal, 2) : $statVal }}</span>
                  </div>
                @empty
                  <div class="p-6 text-center text-xs text-gray-500">Chưa có dữ liệu cầu thủ.</div>
                @endforelse
              </div>
            </div>
          @endforeach
        </div>
      @endif

      <!-- =================================================================== -->
      <!-- TAB: ĐỘI BÓNG HÀNG ĐẦU (TOP TEAMS)                                  -->
      <!-- =================================================================== -->
      @if($tab === 'teams')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @php
            $teamCats = [
              'goalsScored' => '⚽ Ghi nhiều bàn nhất',
              'assists' => '🎯 Kiến tạo nhiều nhất',
              'cleanSheets' => '🧤 Giữ sạch lưới nhiều nhất',
              'avgRating' => '⭐ Điểm đánh giá trung bình',
            ];
          @endphp
          @foreach($teamCats as $tk => $tTitle)
            @php $tlist = $topTeams[$tk] ?? []; @endphp
            @if(!empty($tlist))
              <div class="bg-[#141720] border border-[#232836] rounded-xl overflow-hidden">
                <div class="bg-[#191d28] px-4 py-2.5 border-b border-[#232836] text-xs font-bold text-white">{{ $tTitle }}</div>
                <div class="divide-y divide-[#1f2433]">
                  @foreach(array_slice($tlist, 0, 8) as $idx => $item)
                    @php
                      $tm = $item['team'] ?? [];
                      $tmLogo = \App\Services\UniScoreApiService::teamLogo($sportKey, $tm['id'] ?? '');
                    @endphp
                    <div class="px-4 py-2.5 flex items-center justify-between text-xs hover:bg-[#1c2130]">
                      <div class="flex items-center gap-3">
                        <span class="w-5 text-center font-bold text-gray-500">{{ $idx + 1 }}</span>
                        <img src="{{ $tmLogo }}" class="w-6 h-6 object-contain" onerror="this.style.display='none'">
                        <span class="font-bold text-white">{{ $tm['name'] ?? 'Team' }}</span>
                      </div>
                      <span class="text-sm font-black text-[#f59e0b]">{{ $item['stat'] ?? '-' }}</span>
                    </div>
                  @endforeach
                </div>
              </div>
            @endif
          @endforeach
          @if(empty($topTeams))
            <div class="col-span-2 bg-[#141720] border border-[#232836] rounded-xl p-10 text-center text-xs text-gray-500">
              Chưa có dữ liệu thống kê đội bóng hàng đầu cho mùa giải này.
            </div>
          @endif
        </div>
      @endif

      <!-- =================================================================== -->
      <!-- TAB: THỐNG KÊ HÀNG ĐẦU (OVERVIEW STATS)                             -->
      <!-- =================================================================== -->
      @if($tab === 'stats')
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-[#141720] border border-[#232836] rounded-xl p-4 text-center">
            <div class="text-xs text-gray-400">Tổng số trận đã đấu</div>
            <div class="text-2xl font-black text-white mt-1">{{ $overviewStats['matches'] ?? '-' }}</div>
          </div>
          <div class="bg-[#141720] border border-[#232836] rounded-xl p-4 text-center">
            <div class="text-xs text-gray-400">Tổng số bàn thắng</div>
            <div class="text-2xl font-black text-[#f59e0b] mt-1">{{ $overviewStats['goals'] ?? '-' }}</div>
          </div>
          <div class="bg-[#141720] border border-[#232836] rounded-xl p-4 text-center">
            <div class="text-xs text-gray-400">Bàn thắng TB / trận</div>
            <div class="text-2xl font-black text-emerald-400 mt-1">{{ $overviewStats['average_goals'] ?? '-' }}</div>
          </div>
          <div class="bg-[#141720] border border-[#232836] rounded-xl p-4 text-center">
            <div class="text-xs text-gray-400">Thẻ vàng / Thẻ đỏ TB</div>
            <div class="text-xl font-black text-yellow-400 mt-1">🟨 {{ $overviewStats['yellow_cards'] ?? '-' }} • 🟥 {{ $overviewStats['red_cards'] ?? '-' }}</div>
          </div>
          <div class="col-span-2 md:col-span-4 bg-[#141720] border border-[#232836] rounded-xl p-4">
            <div class="text-xs font-bold text-white mb-3">Tỷ lệ kết quả mùa giải</div>
            <div class="grid grid-cols-3 gap-3 text-center text-xs">
              <div class="bg-[#0f1219] p-3 rounded-lg border border-[#232836]">
                <div class="text-gray-400">Chủ nhà thắng</div>
                <div class="text-lg font-black text-blue-400 mt-1">{{ $overviewStats['home_team_wins'] ?? '-' }}</div>
              </div>
              <div class="bg-[#0f1219] p-3 rounded-lg border border-[#232836]">
                <div class="text-gray-400">Hòa</div>
                <div class="text-lg font-black text-gray-300 mt-1">{{ $overviewStats['draws'] ?? '-' }}</div>
              </div>
              <div class="bg-[#0f1219] p-3 rounded-lg border border-[#232836]">
                <div class="text-gray-400">Đội khách thắng</div>
                <div class="text-lg font-black text-[#f59e0b] mt-1">{{ isset($overviewStats['away_team_wins']) ? round((float)$overviewStats['away_team_wins'], 1) . '%' : '-' }}</div>
              </div>
            </div>
          </div>
        </div>
      @endif

      <!-- =================================================================== -->
      <!-- TAB: CÚP (HONORS / TITLE HOLDER / MOST TITLES)                      -->
      <!-- =================================================================== -->
      @if($tab === 'cup')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @if(!empty($info['titleHolder']))
            <div class="bg-[#141720] border border-[#232836] rounded-xl p-5 flex items-center gap-4">
              <img src="{{ \App\Services\UniScoreApiService::teamLogo($sportKey, $info['titleHolder']['id'] ?? '') }}"
                   class="w-14 h-14 object-contain" onerror="this.style.display='none'">
              <div>
                <div class="text-xs text-[#f59e0b] font-bold uppercase">🏆 Đương Kim Vô Địch</div>
                <div class="text-lg font-black text-white">{{ $info['titleHolder']['name'] ?? '' }}</div>
                <div class="text-xs text-gray-400">Số lần vô địch: {{ $info['titleHolderTitles'] ?? '-' }}</div>
              </div>
            </div>
          @endif

          @if(!empty($info['mostTitlesTeams']))
            <div class="bg-[#141720] border border-[#232836] rounded-xl p-5">
              <div class="text-xs text-blue-400 font-bold uppercase mb-2">👑 Đội Vô Địch Nhiều Nhất ({{ $info['mostTitles'] ?? '' }} lần)</div>
              <div class="space-y-2">
                @foreach($info['mostTitlesTeams'] as $mt)
                  <div class="flex items-center gap-3">
                    <img src="{{ \App\Services\UniScoreApiService::teamLogo($sportKey, $mt['id'] ?? '') }}" class="w-8 h-8 object-contain" onerror="this.style.display='none'">
                    <span class="text-sm font-bold text-white">{{ $mt['name'] ?? '' }}</span>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        </div>
      @endif

    </main>
  </div>

  <script>
    if (document.getElementById('compRangePicker')) {
      flatpickr("#compRangePicker", {
        mode: "range",
        dateFormat: "Y-m-d",
        onClose: function(selectedDates) {
          if (selectedDates.length === 1) {
            const d = flatpickr.formatDate(selectedDates[0], "Y-m-d");
            document.getElementById('compDateFrom').value = d;
            document.getElementById('compDateTo').value = d;
          } else if (selectedDates.length === 2) {
            document.getElementById('compDateFrom').value = flatpickr.formatDate(selectedDates[0], "Y-m-d");
            document.getElementById('compDateTo').value = flatpickr.formatDate(selectedDates[1], "Y-m-d");
          }
        }
      });
    }
  </script>
</body>
</html>
