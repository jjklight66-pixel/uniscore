<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $comp['info']['name'] ?? 'Chi tiết giải đấu' }} - UNISCORE</title>
  @include('partials.fl-head')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <style>
    .fl-zone-top { box-shadow: inset 3px 0 0 #2563EB; }
    .fl-zone-rel { box-shadow: inset 3px 0 0 var(--alert); }
    .fl-field-table th {
      text-align: left;
      font-weight: 600;
      color: var(--ink-2);
      padding: 9px 10px;
      border-bottom: 1px solid var(--chalk);
      background: var(--flood-2);
    }
    .fl-field-table td {
      padding: 8px 10px;
      border-bottom: 1px solid var(--chalk);
    }
    .fl-field-table tbody tr:last-child td { border-bottom: none; }
    .fl-field-table tbody tr:hover { background: var(--flood-2); }
    .tr-dir-btn {
      padding: 5px 12px;
      font-size: 12px;
      font-weight: 600;
      border-radius: 3px;
      color: var(--mist);
      background: transparent;
      white-space: nowrap;
      transition: color .15s, background-color .15s;
    }
    .tr-dir-btn:hover { color: var(--frost); }
    .tr-dir-btn.is-active { background: var(--frost); color: var(--ink); font-weight: 700; }
    .tr-dir-btn.is-active-in { background: var(--goal); color: #052012; font-weight: 700; }
    .tr-dir-btn.is-active-out { background: var(--alert); color: #fff; font-weight: 700; }
  </style>
</head>
<body class="fl min-h-screen flex flex-col">

  <!-- TOP HEADER -->
  <header class="bg-stand border-b border-seat sticky top-0 z-50">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-6 h-14 flex items-center justify-between gap-3">
      <div class="flex items-center gap-4 min-w-0">
        <a href="{{ route('home') }}" class="flex items-center font-score text-2xl font-extrabold tracking-tight select-none shrink-0">
          <span class="text-frost">UNI</span><span class="text-live">SCORE</span>
        </a>

        <form action="{{ route('home') }}" method="GET" class="hidden sm:flex items-center relative">
          <input type="hidden" name="sport" value="{{ $sportKey }}">
          <input type="text" name="q" value="" placeholder="Tìm đội bóng, giải đấu..."
                 class="fl-input w-56 lg:w-72 pl-3 pr-3">
        </form>
      </div>

      <div class="flex items-center gap-2.5 shrink-0">
        <div class="hidden md:flex items-center gap-2 px-2.5 py-1 rounded bg-night border border-seat text-xs font-semibold text-frost">
          <span class="fl-live-dot"></span>
          <span>Live</span>
          <span class="font-score text-sm text-live fl-num">{{ $liveCount ?? 0 }}</span>
        </div>
        <a href="http://localhost:8501" target="_blank" class="fl-btn">
          <span class="hidden sm:inline">Mission Control</span>
          <span class="sm:hidden">Mission</span>
        </a>
        <a href="{{ route('admin.index') }}" class="fl-btn">Quản trị</a>
      </div>
    </div>

    <!-- SPORT TAB BAR -->
    <nav class="border-t border-seat bg-stand" aria-label="Môn thể thao">
      <div class="max-w-[1440px] mx-auto px-4 lg:px-6 flex items-center gap-1 overflow-x-auto">
        @foreach($sports as $k => $sInfo)
          <a href="{{ route('home', ['sport' => $k]) }}"
             class="fl-tab px-3 py-2.5 text-xs flex items-center gap-1.5 shrink-0 {{ $sportKey === $k ? 'is-active' : '' }}">
            <span>{{ $sInfo['icon'] }}</span>
            <span>{{ $sInfo['name'] }}</span>
          </a>
        @endforeach
      </div>
    </nav>
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
  <div class="flex-1 max-w-[1440px] w-full mx-auto px-3 lg:px-6 py-5 flex gap-5 items-start">

    <!-- LEFT SIDEBAR: TOP LEAGUES -->
    <aside class="hidden lg:flex flex-col w-[240px] shrink-0 fl-panel p-3 sticky top-[116px] max-h-[calc(100vh-132px)] overflow-y-auto">
      <h2 class="text-xs font-semibold text-frost pb-2.5 mb-2 border-b border-seat">Giải đấu nổi bật</h2>
      <div class="space-y-0.5">
        @foreach($topLeagues as $lg)
          <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $lg['id']]) }}"
             class="flex items-center gap-2.5 px-2.5 py-2 rounded transition text-xs {{ $lg['id'] === $compId ? 'bg-stand2 text-live font-semibold shadow-[inset_3px_0_0_var(--amber)]' : 'text-frost/85 hover:bg-stand2 hover:text-frost' }}">
            <img src="{{ $lg['logo'] }}" alt="" class="w-5 h-5 object-contain rounded-sm bg-night p-0.5 shrink-0" onerror="this.style.display='none'">
            <span class="truncate">{{ $lg['name'] }}</span>
          </a>
        @endforeach
      </div>
    </aside>

    <!-- MAIN COMPETITION CONTENT -->
    <main class="flex-1 min-w-0 space-y-5">

      <!-- NAVY HERO BAND -->
      <section class="fl-panel overflow-hidden" style="border-radius: var(--r-lg);">
        <div class="p-5 flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-4 min-w-0">
            <img src="{{ $comp['logo'] }}" alt="{{ $compName }}"
                 class="w-16 h-16 object-contain rounded-lg bg-night p-2 border border-seat shrink-0"
                 onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($compName,0,2)) }}&background=0A1B30&color=FFB020&size=64'">
            <div class="min-w-0">
              <h1 class="fl-display text-3xl sm:text-4xl text-frost truncate">{{ $compName }}</h1>
              <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-mist">
                <span>{{ $countryName }}</span>
                @if(!empty($seasons))
                  <form action="{{ route('competition.show', ['sport' => $sportKey, 'id' => $compId]) }}" method="GET" class="inline-block">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <select name="season" onchange="this.form.submit()" class="fl-input py-1 px-2.5 text-xs">
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

          <button type="button" class="fl-btn text-xs" title="Theo dõi giải đấu">
            <span>☆</span>
            <span>Theo dõi</span>
          </button>
        </div>

        <!-- COMPETITION TABS (.fl-tab underline bar) -->
        <div class="border-t border-seat px-4 flex items-center gap-1 overflow-x-auto text-xs">
          @php
            $compTabs = [
              'overview' => 'Tổng quan',
              'matches' => 'Lịch & Kết quả',
              'standings' => 'Bảng xếp hạng',
              'bracket' => 'Nhánh đấu',
              'transfers' => 'Chuyển nhượng',
              'players' => 'Cầu thủ nổi bật',
              'teams' => 'Đội bóng hàng đầu',
              'stats' => 'Thống kê',
              'cup' => 'Vinh danh',
            ];
          @endphp
          @foreach($compTabs as $tKey => $tLabel)
            <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $compId, 'season' => $activeSeason, 'tab' => $tKey]) }}"
               class="fl-tab px-3 py-3 transition {{ $tab === $tKey ? 'is-active' : '' }}">
              {{ $tLabel }}
            </a>
          @endforeach
        </div>
      </section>

      <!-- =================================================================== -->
      <!-- TAB: TỔNG QUAN / DANH SÁCH TRẬN ĐẤU (UPCOMING + FINISHED + RANGE)   -->
      <!-- =================================================================== -->
      @if($tab === 'overview' || $tab === 'matches')
        <!-- Date Range Filter Bar -->
        <div class="fl-panel p-3.5 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="text-frost font-medium">
            Lọc theo ngày
            <span class="fl-muted ml-2 fl-num">{{ count($upcomingEvents) }} sắp đấu, {{ count($finishedEvents) }} đã kết thúc</span>
          </div>
          <form id="compDateRangeForm" action="{{ route('competition.show', ['sport' => $sportKey, 'id' => $compId]) }}" method="GET" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="season" value="{{ $activeSeason }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" id="compDateFrom" name="date_from" value="{{ $dateFrom }}">
            <input type="hidden" id="compDateTo" name="date_to" value="{{ $dateTo }}">
            <input type="text" id="compRangePicker" readonly
                   value="{{ ($dateFrom && $dateTo) ? ($dateFrom . ' to ' . $dateTo) : '' }}"
                   placeholder="Chọn khoảng ngày..."
                   class="fl-input w-52 cursor-pointer">
            <button type="submit" class="fl-btn fl-btn--primary">Lọc</button>
            @if($dateFrom || $dateTo)
              <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $compId, 'season' => $activeSeason, 'tab' => $tab]) }}"
                 class="fl-btn">Xóa lọc</a>
            @endif
          </form>
        </div>

        <!-- UPCOMING MATCHES (FLOODLIT FIELD) -->
        @if(!empty($upcomingEvents))
          <section class="fl-field border border-chalk overflow-hidden">
            <div class="bg-flood2 px-4 py-2.5 border-b border-chalk flex justify-between items-center">
              <h2 class="text-xs font-semibold text-ink">Trận đấu sắp tới</h2>
              <span class="text-xs fl-muted fl-num">{{ count($upcomingEvents) }} trận</span>
            </div>
            <div class="divide-y divide-chalk">
              @foreach(($tab === 'overview' ? array_slice($upcomingEvents, 0, 8) : $upcomingEvents) as $ev)
                @php
                  $hTeam = $ev['homeTeam'] ?? [];
                  $aTeam = $ev['awayTeam'] ?? [];
                  $hLogo = \App\Services\UniScoreApiService::teamLogo($sportKey, $hTeam['id'] ?? '');
                  $aLogo = \App\Services\UniScoreApiService::teamLogo($sportKey, $aTeam['id'] ?? '');
                  $ts = (int)($ev['startTimestamp'] ?? 0);
                @endphp
                <div class="px-4 py-2.5 hover:bg-flood2 flex items-center justify-between gap-3 transition-colors">
                  <div class="w-20 sm:w-24 text-xs fl-muted fl-num shrink-0">{{ $ts ? date('d/m/Y', $ts) : '' }}</div>
                  <div class="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-2.5 sm:gap-3 min-w-0">
                    <div class="flex items-center justify-end gap-2 min-w-0">
                      <span class="text-xs font-semibold text-ink text-right truncate">{{ $hTeam['name'] ?? 'Home' }}</span>
                      <img src="{{ $hLogo }}" alt="" class="w-5 h-5 object-contain shrink-0" onerror="this.style.display='none'">
                    </div>
                    <div class="px-2.5 py-0.5 rounded bg-white border border-chalk font-score text-sm text-ink min-w-[54px] text-center fl-num">
                      {{ $ts ? date('H:i', $ts) : 'vs' }}
                    </div>
                    <div class="flex items-center justify-start gap-2 min-w-0">
                      <img src="{{ $aLogo }}" alt="" class="w-5 h-5 object-contain shrink-0" onerror="this.style.display='none'">
                      <span class="text-xs font-semibold text-ink truncate">{{ $aTeam['name'] ?? 'Away' }}</span>
                    </div>
                  </div>
                  <div class="hidden sm:block w-16 text-right text-xs fl-muted shrink-0">Sắp đấu</div>
                </div>
              @endforeach
            </div>
          </section>
        @endif

        <!-- FINISHED / RECENT MATCHES (FLOODLIT FIELD) -->
        <section class="fl-field border border-chalk overflow-hidden">
          <div class="bg-flood2 px-4 py-2.5 border-b border-chalk flex flex-wrap justify-between items-center gap-2">
            <h2 class="text-xs font-semibold text-ink">Kết quả gần nhất <span class="fl-muted font-normal">(Trang {{ $activePage }})</span></h2>
            <div class="flex items-center gap-2 text-xs">
              @if($activePage > 1)
                <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $compId, 'season' => $activeSeason, 'tab' => $tab, 'page' => $activePage - 1]) }}"
                   class="px-2.5 py-1 rounded bg-white border border-chalk text-ink font-medium hover:bg-flood2 transition">Trang trước</a>
              @endif
              <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $compId, 'season' => $activeSeason, 'tab' => $tab, 'page' => $activePage + 1]) }}"
                 class="px-2.5 py-1 rounded bg-white border border-chalk text-ink font-medium hover:bg-flood2 transition">Trang sau</a>
            </div>
          </div>
          <div class="divide-y divide-chalk">
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
              <div class="px-4 py-2.5 hover:bg-flood2 flex items-center justify-between gap-3 transition-colors">
                <div class="w-20 sm:w-24 text-xs fl-muted fl-num shrink-0">{{ $ts ? date('d/m/Y', $ts) : 'FT' }}</div>
                <div class="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-2.5 sm:gap-3 min-w-0">
                  <div class="flex items-center justify-end gap-2 min-w-0">
                    <span class="text-xs font-semibold text-ink text-right truncate">{{ $hTeam['name'] ?? 'Home' }}</span>
                    <img src="{{ $hLogo }}" alt="" class="w-5 h-5 object-contain shrink-0" onerror="this.style.display='none'">
                  </div>
                  <div class="px-2.5 py-0.5 rounded bg-white border border-chalk font-score text-base font-bold text-ink min-w-[54px] text-center fl-num">
                    {{ $hScore }} - {{ $aScore }}
                  </div>
                  <div class="flex items-center justify-start gap-2 min-w-0">
                    <img src="{{ $aLogo }}" alt="" class="w-5 h-5 object-contain shrink-0" onerror="this.style.display='none'">
                    <span class="text-xs font-semibold text-ink truncate">{{ $aTeam['name'] ?? 'Away' }}</span>
                  </div>
                </div>
                <div class="hidden sm:block w-16 text-right text-xs fl-muted shrink-0">Kết thúc</div>
              </div>
            @empty
              <div class="p-8 text-center text-xs fl-muted">Không có trận đấu nào trong khoảng thời gian đã chọn.</div>
            @endforelse
          </div>
        </section>
      @endif

      <!-- =================================================================== -->
      <!-- TAB: BẢNG XẾP HẠNG (STANDINGS ON FLOODLIT FIELD)                    -->
      <!-- =================================================================== -->
      @if($tab === 'standings' || $tab === 'overview')
        <section class="fl-field border border-chalk overflow-hidden">
          <div class="bg-flood2 px-4 py-3 border-b border-chalk flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-xs font-semibold text-ink">Bảng xếp hạng</h2>
            <div class="flex items-center gap-4 text-[11px] fl-muted">
              <span class="inline-flex items-center gap-1.5"><span class="w-1 h-3 rounded-sm bg-blue-600 inline-block"></span> Nhóm dẫn đầu</span>
              <span class="inline-flex items-center gap-1.5"><span class="w-1 h-3 rounded-sm bg-alert inline-block"></span> Nhóm xuống hạng</span>
            </div>
          </div>
          @forelse($standingsGroups as $stGroup)
            @php
              $rows = $stGroup['rows'] ?? [];
              $rawGroupTitle = ltrim((string)($stGroup['name'] ?? ''), '_');
              $groupTitle = preg_replace('/_[a-z0-9]{8,}$/i', '', $rawGroupTitle);
              $isBkb = ($sportKey === 'basketball');
            @endphp
            @if(!empty($rows))
              @if($groupTitle)
                <div class="bg-white px-4 py-2 text-xs font-semibold text-liveink border-t border-chalk">
                  {{ $groupTitle }}
                </div>
              @endif
              <div class="overflow-x-auto">
                <table class="fl-field-table w-full text-xs">
                  <thead>
                    <tr>
                      <th class="w-10 text-center">#</th>
                      <th>Đội bóng</th>
                      <th class="text-center">Trận</th>
                      <th class="text-center">T</th>
                      @if(!$isBkb)<th class="text-center">H</th>@endif
                      <th class="text-center">B</th>
                      @if(!$isBkb)
                        <th class="text-center">BT:BB</th>
                        <th class="text-center">HS</th>
                        <th class="text-center font-bold text-ink">Điểm</th>
                      @else
                        <th class="text-center font-bold text-ink">Tỷ lệ</th>
                      @endif
                    </tr>
                  </thead>
                  <tbody>
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
                        $zoneClass = (is_numeric($pos) && $pos <= 4) ? 'fl-zone-top' : ((is_numeric($pos) && count($rows) > 6 && $pos >= count($rows) - 2) ? 'fl-zone-rel' : '');
                      @endphp
                      <tr class="{{ $zoneClass }}">
                        <td class="text-center font-score text-sm fl-num {{ (is_numeric($pos) && $pos <= 4) ? 'text-blue-700' : ((is_numeric($pos) && count($rows) > 6 && $pos >= count($rows) - 2) ? 'text-alert' : 'text-ink2') }}">{{ $pos }}</td>
                        <td>
                          <div class="flex items-center gap-2.5">
                            <img src="{{ $tmLogo }}" alt="" class="w-5 h-5 object-contain shrink-0" onerror="this.style.display='none'">
                            <span class="font-semibold text-ink">{{ $tm['name'] ?? 'Team' }}</span>
                          </div>
                        </td>
                        <td class="text-center text-ink2 fl-num">{{ $mCount }}</td>
                        <td class="text-center text-emerald-700 font-medium fl-num">{{ $wCount }}</td>
                        @if(!$isBkb)<td class="text-center text-ink2 fl-num">{{ $row['draws'] ?? 0 }}</td>@endif
                        <td class="text-center text-alert fl-num">{{ $row['losses'] ?? 0 }}</td>
                        @if(!$isBkb)
                          <td class="text-center text-ink2 fl-num">{{ $gf }}:{{ $ga }}</td>
                          <td class="text-center fl-num {{ $diff > 0 ? 'text-emerald-700 font-medium' : ($diff < 0 ? 'text-alert' : 'text-ink2') }}">{{ $diff > 0 ? '+' . $diff : $diff }}</td>
                          <td class="text-center font-score text-base font-bold text-liveink fl-num">{{ $row['points'] ?? 0 }}</td>
                        @else
                          <td class="text-center font-score text-base font-bold text-liveink fl-num">{{ $pct }}</td>
                        @endif
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          @empty
            <div class="p-6 text-center text-xs fl-muted">Chưa có bảng xếp hạng cho mùa giải này.</div>
          @endforelse
        </section>
      @endif

      <!-- =================================================================== -->
      <!-- TAB: NHÁNH ĐẤU (BRACKET / KNOCKOUT TREE)                            -->
      <!-- =================================================================== -->
      @if($tab === 'bracket')
        @php
          $bracketData = $comp['bracket'] ?? null;
          $bracketRounds = is_array($bracketData) ? ($bracketData['rounds'] ?? (isset($bracketData[0]['blocks']) ? $bracketData : [])) : [];
        @endphp
        <section class="fl-field border border-chalk p-5">
          <h2 class="text-sm font-semibold text-ink mb-4">Nhánh đấu loại trực tiếp</h2>
          @if(!empty($bracketRounds))
            <div class="flex gap-4 overflow-x-auto pb-3">
              @foreach($bracketRounds as $brRound)
                <div class="min-w-[230px] shrink-0 space-y-3">
                  <div class="text-xs font-semibold text-ink bg-flood2 px-3 py-2 rounded border border-chalk text-center">
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
                      <div class="bg-white border border-chalk rounded-lg p-3 space-y-2 text-xs">
                        <div class="flex justify-between items-center gap-2">
                          <div class="flex items-center gap-2 min-w-0">
                            <img src="{{ $t1Logo }}" alt="" class="w-4 h-4 object-contain shrink-0" onerror="this.style.display='none'">
                            <span class="truncate {{ !empty($p1['winner']) ? 'text-ink font-bold' : 'text-ink2' }}">{{ $p1['team']['name'] ?? 'Chờ xác định' }}</span>
                          </div>
                          <span class="font-score text-sm fl-num {{ !empty($p1['winner']) ? 'text-liveink font-bold' : 'text-ink2' }}">{{ $blk['homeTeamScore'] ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                          <div class="flex items-center gap-2 min-w-0">
                            <img src="{{ $t2Logo }}" alt="" class="w-4 h-4 object-contain shrink-0" onerror="this.style.display='none'">
                            <span class="truncate {{ !empty($p2['winner']) ? 'text-ink font-bold' : 'text-ink2' }}">{{ $p2['team']['name'] ?? 'Chờ xác định' }}</span>
                          </div>
                          <span class="font-score text-sm fl-num {{ !empty($p2['winner']) ? 'text-liveink font-bold' : 'text-ink2' }}">{{ $blk['awayTeamScore'] ?? '-' }}</span>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="p-8 text-center text-xs fl-muted">
              Giải đấu hoặc mùa giải này hiện thi đấu theo thể thức bảng tính điểm, chưa mở nhánh đấu loại trực tiếp.
            </div>
          @endif
        </section>
      @endif

      <!-- =================================================================== -->
      <!-- TAB: CHUYỂN NHƯỢNG (TRANSFERS)                                      -->
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

        <!-- Filter Bar -->
        <div class="fl-panel p-3.5 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="fl-seg">
            <button type="button" onclick="filterTransfers('all', this)" class="tr-dir-btn is-active">
              Tất cả
            </button>
            <button type="button" onclick="filterTransfers('in', this)" class="tr-dir-btn">
              Gia nhập
            </button>
            <button type="button" onclick="filterTransfers('out', this)" class="tr-dir-btn">
              Rời đội
            </button>
          </div>

          <div class="flex items-center gap-2">
            <label for="trClubFilter" class="text-mist">Câu lạc bộ:</label>
            <select id="trClubFilter" onchange="applyTransferFilters()" class="fl-input">
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
              <div class="transfer-club-card fl-field border border-chalk overflow-hidden" data-club-id="{{ $clubId }}">
                <div class="bg-flood2 px-4 py-2.5 border-b border-chalk flex items-center justify-between">
                  <div class="flex items-center gap-2.5">
                    <img src="{{ $clubLogo }}" alt="" class="w-5 h-5 object-contain shrink-0" onerror="this.style.display='none'">
                    <h3 class="text-xs font-semibold text-ink">{{ $clubName }}</h3>
                  </div>
                  <span class="text-xs fl-muted fl-num">{{ count($players) }} thương vụ</span>
                </div>

                <div class="divide-y divide-chalk">
                  @foreach($players as $item)
                    @php
                      $pl = $item['player'] ?? [];
                      $pId = $pl['id'] ?? '';
                      $pName = $pl['name'] ?? ($pl['shortName'] ?? 'Cầu thủ');
                      $pPos = $pl['position'] ?? '';
                      $pAvatar = \App\Services\UniScoreApiService::playerAvatar($sportKey, $pId);

                      $dir = strtolower($item['direction'] ?? 'out');
                      $isIn = ($dir === 'in');

                      $otherTeam = $isIn ? ($item['transferFrom'] ?? []) : ($item['transferTo'] ?? []);
                      $otherId = $otherTeam['id'] ?? '';
                      $otherName = $otherTeam['name'] ?? 'Chưa rõ CLB';
                      $otherLogo = \App\Services\UniScoreApiService::teamLogo($sportKey, $otherId);

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
                    <div class="transfer-row px-4 py-2.5 grid grid-cols-[1fr_auto_1fr] items-center gap-3 hover:bg-flood2 transition-colors text-xs"
                         data-direction="{{ $dir }}">

                      <div class="flex items-center gap-3 min-w-0">
                        <img src="{{ $pAvatar }}" alt="{{ $pName }}"
                             class="w-9 h-9 rounded-full object-cover bg-white border border-chalk shrink-0"
                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($pName,0,2)) }}&background=E6ECF3&color=0C1A2C&size=40'">
                        <div class="min-w-0">
                          <div class="flex items-center gap-1.5">
                            <span class="font-semibold text-ink truncate">{{ $pName }}</span>
                            @if($pPos)
                              <span class="text-[11px] px-1.5 py-0.5 rounded bg-white border border-chalk text-ink2">{{ $pPos }}</span>
                            @endif
                          </div>
                          <div class="text-[11px] fl-muted flex items-center gap-1.5 mt-0.5">
                            <span>{{ $feeDesc }}</span>
                            @if($feeAmountStr)
                              <span class="text-emerald-700 font-semibold fl-num">{{ $feeAmountStr }}</span>
                            @endif
                          </div>
                        </div>
                      </div>

                      <div class="px-2 sm:px-3 text-center">
                        @if($isIn)
                          <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 font-bold text-xs" title="Gia nhập {{ $clubName }}">←</span>
                        @else
                          <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-alert font-bold text-xs" title="Rời {{ $clubName }}">→</span>
                        @endif
                      </div>

                      <div class="flex items-center justify-end gap-2.5 min-w-0 text-right">
                        <div class="min-w-0">
                          <div class="text-xs font-semibold text-ink truncate">{{ $otherName }}</div>
                          @if($trDateLabel)
                            <div class="text-[11px] fl-muted fl-num">{{ $trDateLabel }}</div>
                          @endif
                        </div>
                        <img src="{{ $otherLogo }}" alt="{{ $otherName }}"
                             class="w-6 h-6 object-contain shrink-0 rounded-sm bg-white p-0.5 border border-chalk"
                             onerror="this.style.display='none'">
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @endif
          @empty
            <div class="fl-field border border-chalk p-10 text-center text-xs fl-muted">
              Chưa có dữ liệu chuyển nhượng cho mùa giải này.
            </div>
          @endforelse
        </div>

        <script>
          let activeTransferDir = 'all';
          function filterTransfers(dir, btn) {
            activeTransferDir = dir;
            document.querySelectorAll('.tr-dir-btn').forEach(b => {
              b.className = 'tr-dir-btn';
            });
            if (dir === 'in') btn.className = 'tr-dir-btn is-active-in';
            else if (dir === 'out') btn.className = 'tr-dir-btn is-active-out';
            else btn.className = 'tr-dir-btn is-active';
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
      <!-- TAB: CẦU THỦ NỔI BẬT (TOP PLAYERS ON FLOODLIT FIELD)                -->
      <!-- =================================================================== -->
      @if($tab === 'players')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @php
            $playerCats = [
              'goals' => ['title' => 'Vua phá lưới', 'key' => 'goals'],
              'assists' => ['title' => 'Vua kiến tạo', 'key' => 'assists'],
              'rating' => ['title' => 'Điểm đánh giá cao nhất', 'key' => 'rating'],
            ];
          @endphp
          @foreach($playerCats as $catKey => $catMeta)
            @php $plist = $topPlayers[$catKey] ?? []; @endphp
            <section class="fl-field border border-chalk overflow-hidden">
              <div class="bg-flood2 px-4 py-2.5 border-b border-chalk text-xs font-semibold text-ink">{{ $catMeta['title'] }}</div>
              <div class="divide-y divide-chalk">
                @forelse(array_slice($plist, 0, 10) as $idx => $item)
                  @php
                    $pl = $item['player'] ?? [];
                    $tm = $item['team'] ?? [];
                    $statVal = $item['statistics'][$catMeta['key']] ?? '-';
                    $avatar = \App\Services\UniScoreApiService::playerAvatar($sportKey, $pl['id'] ?? '');
                  @endphp
                  <div class="px-4 py-2.5 flex items-center justify-between text-xs hover:bg-flood2 transition-colors">
                    <div class="flex items-center gap-3 min-w-0">
                      <span class="w-5 text-center font-score text-sm fl-muted fl-num">{{ $idx + 1 }}</span>
                      <img src="{{ $avatar }}" alt="" class="w-8 h-8 rounded-full object-cover bg-white border border-chalk shrink-0"
                           onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($pl['name'] ?? 'P',0,2)) }}&background=E6ECF3&color=0C1A2C&size=32'">
                      <div class="min-w-0">
                        <div class="font-semibold text-ink truncate">{{ $pl['name'] ?? 'Player' }}</div>
                        <div class="text-[11px] fl-muted truncate">{{ $tm['name'] ?? '' }}</div>
                      </div>
                    </div>
                    <span class="font-score text-base font-bold text-liveink fl-num">{{ is_numeric($statVal) && $catKey === 'rating' ? number_format($statVal, 2) : $statVal }}</span>
                  </div>
                @empty
                  <div class="p-6 text-center text-xs fl-muted">Chưa có dữ liệu cầu thủ.</div>
                @endforelse
              </div>
            </section>
          @endforeach
        </div>
      @endif

      <!-- =================================================================== -->
      <!-- TAB: ĐỘI BÓNG HÀNG ĐẦU (TOP TEAMS ON FLOODLIT FIELD)                -->
      <!-- =================================================================== -->
      @if($tab === 'teams')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @php
            $teamCats = [
              'goalsScored' => 'Ghi nhiều bàn nhất',
              'assists' => 'Kiến tạo nhiều nhất',
              'cleanSheets' => 'Giữ sạch lưới nhiều nhất',
              'avgRating' => 'Điểm đánh giá trung bình',
            ];
          @endphp
          @foreach($teamCats as $tk => $tTitle)
            @php $tlist = $topTeams[$tk] ?? []; @endphp
            @if(!empty($tlist))
              <section class="fl-field border border-chalk overflow-hidden">
                <div class="bg-flood2 px-4 py-2.5 border-b border-chalk text-xs font-semibold text-ink">{{ $tTitle }}</div>
                <div class="divide-y divide-chalk">
                  @foreach(array_slice($tlist, 0, 8) as $idx => $item)
                    @php
                      $tm = $item['team'] ?? [];
                      $tmLogo = \App\Services\UniScoreApiService::teamLogo($sportKey, $tm['id'] ?? '');
                    @endphp
                    <div class="px-4 py-2.5 flex items-center justify-between text-xs hover:bg-flood2 transition-colors">
                      <div class="flex items-center gap-3 min-w-0">
                        <span class="w-5 text-center font-score text-sm fl-muted fl-num">{{ $idx + 1 }}</span>
                        <img src="{{ $tmLogo }}" alt="" class="w-6 h-6 object-contain shrink-0" onerror="this.style.display='none'">
                        <span class="font-semibold text-ink truncate">{{ $tm['name'] ?? 'Team' }}</span>
                      </div>
                      <span class="font-score text-base font-bold text-liveink fl-num">{{ $item['stat'] ?? '-' }}</span>
                    </div>
                  @endforeach
                </div>
              </section>
            @endif
          @endforeach
          @if(empty($topTeams))
            <div class="col-span-2 fl-field border border-chalk p-10 text-center text-xs fl-muted">
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
          <div class="fl-panel p-4 text-center">
            <div class="text-xs text-mist">Tổng số trận đã đấu</div>
            <div class="fl-display text-3xl text-frost mt-1.5 fl-num">{{ $overviewStats['matches'] ?? '-' }}</div>
          </div>
          <div class="fl-panel p-4 text-center">
            <div class="text-xs text-mist">Tổng số bàn thắng</div>
            <div class="fl-display text-3xl text-live mt-1.5 fl-num">{{ $overviewStats['goals'] ?? '-' }}</div>
          </div>
          <div class="fl-panel p-4 text-center">
            <div class="text-xs text-mist">Bàn thắng TB / trận</div>
            <div class="fl-display text-3xl text-goal mt-1.5 fl-num">{{ $overviewStats['average_goals'] ?? '-' }}</div>
          </div>
          <div class="fl-panel p-4 text-center">
            <div class="text-xs text-mist">Thẻ vàng / Thẻ đỏ TB</div>
            <div class="fl-display text-2xl text-frost mt-2 fl-num">
              <span class="text-live">{{ $overviewStats['yellow_cards'] ?? '-' }}</span>
              <span class="text-mist mx-1">/</span>
              <span class="text-alert">{{ $overviewStats['red_cards'] ?? '-' }}</span>
            </div>
          </div>
          <div class="col-span-2 md:col-span-4 fl-field border border-chalk p-4">
            <div class="text-xs font-semibold text-ink mb-3">Tỷ lệ kết quả mùa giải</div>
            <div class="grid grid-cols-3 gap-3 text-center text-xs">
              <div class="bg-white p-3 rounded border border-chalk">
                <div class="fl-muted">Chủ nhà thắng</div>
                <div class="font-score text-2xl font-bold text-blue-700 mt-1 fl-num">{{ $overviewStats['home_team_wins'] ?? '-' }}</div>
              </div>
              <div class="bg-white p-3 rounded border border-chalk">
                <div class="fl-muted">Hòa</div>
                <div class="font-score text-2xl font-bold text-ink2 mt-1 fl-num">{{ $overviewStats['draws'] ?? '-' }}</div>
              </div>
              <div class="bg-white p-3 rounded border border-chalk">
                <div class="fl-muted">Đội khách thắng</div>
                <div class="font-score text-2xl font-bold text-liveink mt-1 fl-num">{{ isset($overviewStats['away_team_wins']) ? round((float)$overviewStats['away_team_wins'], 1) . '%' : '-' }}</div>
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
            <div class="fl-panel p-5 flex items-center gap-4">
              <img src="{{ \App\Services\UniScoreApiService::teamLogo($sportKey, $info['titleHolder']['id'] ?? '') }}"
                   alt="" class="w-14 h-14 object-contain shrink-0" onerror="this.style.display='none'">
              <div>
                <div class="text-xs text-live font-semibold">Đương kim vô địch</div>
                <div class="fl-display text-2xl text-frost mt-0.5">{{ $info['titleHolder']['name'] ?? '' }}</div>
                <div class="text-xs text-mist mt-1 fl-num">Số lần vô địch: {{ $info['titleHolderTitles'] ?? '-' }}</div>
              </div>
            </div>
          @endif

          @if(!empty($info['mostTitlesTeams']))
            <div class="fl-panel p-5">
              <div class="text-xs text-frost font-semibold mb-3 fl-num">Đội vô địch nhiều nhất ({{ $info['mostTitles'] ?? '' }} lần)</div>
              <div class="space-y-2.5">
                @foreach($info['mostTitlesTeams'] as $mt)
                  <div class="flex items-center gap-3">
                    <img src="{{ \App\Services\UniScoreApiService::teamLogo($sportKey, $mt['id'] ?? '') }}" alt="" class="w-8 h-8 object-contain shrink-0" onerror="this.style.display='none'">
                    <span class="text-sm font-semibold text-frost">{{ $mt['name'] ?? '' }}</span>
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
