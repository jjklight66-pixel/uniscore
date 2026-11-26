<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $teamData['team']['name'] ?? 'Hồ sơ đội bóng' }} - {{ $siteSettings['site_name'] ?? 'UNISCORE' }}</title>
  @if(!empty($siteSettings['site_favicon_url']))
    <link rel="icon" href="{{ $siteSettings['site_favicon_url'] }}">
  @endif
  @include('partials.fl-head')
</head>
<body class="fl min-h-screen flex flex-col">
  <!-- TOP HEADER -->
  <header class="bg-stand border-b border-seat sticky top-0 z-50">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-6 h-14 flex items-center justify-between gap-3">
      <div class="flex items-center gap-4 min-w-0">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-score text-2xl font-extrabold tracking-tight select-none shrink-0">
          @include('partials.fl-brand')
        </a>
        <form action="{{ route('home') }}" method="GET" class="hidden sm:flex items-center">
          <input type="hidden" name="sport" value="{{ $sportKey }}">
          <input type="text" name="q" placeholder="Tìm đội bóng, giải đấu..." class="fl-input w-56 lg:w-72">
        </form>
      </div>
      <div class="flex items-center gap-2.5 shrink-0">
        <div class="hidden md:flex items-center gap-2 px-2.5 py-1 rounded bg-night border border-seat text-xs font-semibold text-frost">
          <span class="fl-live-dot"></span>
          <span>Live</span>
          <span class="font-score text-sm text-live fl-num">{{ $liveCount ?? 0 }}</span>
        </div>
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
    $team = $teamData['team'] ?? [];
    $teamName = $team['name'] ?? 'Đội bóng';
    $countryName = $team['country']['name'] ?? 'Quốc tế';
    $venueName = $team['venue']['stadium']['name'] ?? ($team['venue']['name'] ?? '');
    $managerName = $team['manager']['name'] ?? '';
    $players = $teamData['players'] ?? [];
    $upcoming = $teamData['upcoming_events'] ?? [];
    $finished = $teamData['finished_events'] ?? [];
    $transfersIn = $teamData['transfers_in'] ?? [];
    $transfersOut = $teamData['transfers_out'] ?? [];
  @endphp

  <!-- NAVY HERO -->
  <section class="bg-stand/70 border-b border-seat">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-6 py-6 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4 min-w-0">
        <img src="{{ $teamData['logo'] }}" alt="{{ $teamName }}"
             class="w-16 h-16 object-contain rounded-[8px] bg-night p-2 border border-seat shrink-0"
             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($teamName,0,2)) }}&background=163152&color=E8EEF6&size=64'">
        <div class="min-w-0">
          <div class="text-xs text-mist">{{ $sports[$sportKey]['icon'] ?? '' }} {{ $sports[$sportKey]['name'] ?? $sportKey }} — {{ $countryName }}</div>
          <h1 class="fl-display text-3xl sm:text-4xl text-frost truncate">{{ $teamName }}</h1>
          <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-mist mt-1">
            @if($venueName) <span>Sân nhà: <b class="text-frost">{{ $venueName }}</b></span> @endif
            @if($managerName) <span>HLV: <b class="text-frost">{{ $managerName }}</b></span> @endif
            <span>Cầu thủ: <b class="text-frost fl-num">{{ count($players) }}</b></span>
          </div>
        </div>
      </div>
      <a href="{{ route('home', ['sport' => $sportKey]) }}" class="fl-btn text-xs">← Danh sách trận đấu</a>
    </div>

    <div class="max-w-[1440px] mx-auto px-4 lg:px-6 flex items-center gap-1 overflow-x-auto">
      @foreach(['overview' => 'Tổng quan', 'matches' => 'Lịch & Kết quả', 'squad' => 'Đội hình', 'transfers' => 'Chuyển nhượng'] as $tk => $tl)
        <a href="{{ route('team.show', ['sport' => $sportKey, 'id' => $teamId, 'tab' => $tk]) }}"
           class="fl-tab px-3.5 py-2.5 text-xs shrink-0 {{ $tab === $tk ? 'is-active' : '' }}">{{ $tl }}</a>
      @endforeach
    </div>
  </section>

  <main class="max-w-[1440px] w-full mx-auto px-4 lg:px-6 py-5 space-y-5 flex-1">
    @if($tab === 'overview' || $tab === 'matches')
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="fl-field border border-chalk overflow-hidden">
          <div class="bg-flood2 px-4 py-2.5 border-b border-chalk flex justify-between items-center">
            <h2 class="text-sm font-semibold text-ink">Lịch thi đấu sắp tới</h2>
            <span class="text-xs fl-muted font-score">{{ count($upcoming) }} trận</span>
          </div>
          <div class="divide-y divide-chalk/70">
            @forelse(array_slice($upcoming, 0, $tab === 'overview' ? 8 : 30) as $ev)
              <div class="px-4 py-2.5 flex items-center justify-between gap-2 text-xs">
                <span class="text-ink2 font-score w-24 shrink-0">{{ !empty($ev['startTimestamp']) ? date('d/m H:i', $ev['startTimestamp']) : '--:--' }}</span>
                <div class="flex-1 flex items-center justify-between gap-2 min-w-0">
                  <span class="font-medium text-ink truncate">{{ $ev['homeTeam']['name'] ?? '' }}</span>
                  <span class="font-score font-bold text-ink2 px-2">vs</span>
                  <span class="font-medium text-ink truncate text-right">{{ $ev['awayTeam']['name'] ?? '' }}</span>
                </div>
              </div>
            @empty
              <div class="p-6 text-center text-xs fl-muted">Chưa có lịch thi đấu sắp tới.</div>
            @endforelse
          </div>
        </div>

        <div class="fl-field border border-chalk overflow-hidden">
          <div class="bg-flood2 px-4 py-2.5 border-b border-chalk flex justify-between items-center">
            <h2 class="text-sm font-semibold text-ink">Kết quả gần đây</h2>
            <span class="text-xs fl-muted font-score">{{ count($finished) }} trận</span>
          </div>
          <div class="divide-y divide-chalk/70">
            @forelse(array_slice($finished, 0, $tab === 'overview' ? 8 : 30) as $ev)
              <div class="px-4 py-2.5 flex items-center justify-between gap-2 text-xs">
                <span class="text-ink2 font-score w-20 shrink-0">{{ !empty($ev['startTimestamp']) ? date('d/m/y', $ev['startTimestamp']) : '' }}</span>
                <div class="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-2 min-w-0">
                  <span class="font-medium text-ink truncate text-right">{{ $ev['homeTeam']['name'] ?? '' }}</span>
                  <span class="font-score text-sm font-bold bg-white border border-chalk rounded px-2 py-0.5 text-ink">
                    {{ $ev['homeScore']['display'] ?? ($ev['homeScore']['current'] ?? '-') }} - {{ $ev['awayScore']['display'] ?? ($ev['awayScore']['current'] ?? '-') }}
                  </span>
                  <span class="font-medium text-ink truncate">{{ $ev['awayTeam']['name'] ?? '' }}</span>
                </div>
              </div>
            @empty
              <div class="p-6 text-center text-xs fl-muted">Chưa có kết quả gần đây.</div>
            @endforelse
          </div>
        </div>
      </div>
    @endif

    @if($tab === 'overview' || $tab === 'squad')
      <div class="fl-field border border-chalk overflow-hidden">
        <div class="bg-flood2 px-4 py-2.5 border-b border-chalk flex justify-between items-center">
          <h2 class="text-sm font-semibold text-ink">Danh sách cầu thủ</h2>
          <span class="text-xs fl-muted font-score">{{ count($players) }} cầu thủ</span>
        </div>
        @if(empty($players))
          <div class="p-6 text-center text-xs fl-muted">Chưa có dữ liệu đội hình cho đội bóng này.</div>
        @else
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 divide-y sm:divide-y-0 gap-px bg-chalk">
            @foreach($players as $p)
              <a href="{{ route('player.show', ['sport' => $sportKey, 'id' => $p['id']]) }}"
                 class="bg-flood hover:bg-flood2 px-4 py-3 flex items-center justify-between gap-3 transition-colors">
                <div class="flex items-center gap-3 min-w-0">
                  <img src="{{ $p['avatar'] }}" alt="{{ $p['name'] }}"
                       class="w-9 h-9 rounded-full object-cover bg-white border border-chalk shrink-0"
                       onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($p['name'],0,2)) }}&background=D3DCE7&color=0C1A2C&size=48'">
                  <div class="min-w-0">
                    <div class="text-[13px] font-semibold text-ink truncate">{{ $p['name'] }}</div>
                    <div class="text-[11px] fl-muted truncate">{{ $p['country'] ?: 'Quốc tế' }} {{ $p['position'] ? '— ' . $p['position'] : '' }}</div>
                  </div>
                </div>
                @if($p['shirt_number'])
                  <span class="font-score text-base font-bold text-liveink bg-white border border-chalk px-2 py-0.5 rounded">#{{ $p['shirt_number'] }}</span>
                @endif
              </a>
            @endforeach
          </div>
        @endif
      </div>
    @endif

    @if($tab === 'transfers')
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="fl-field border border-chalk overflow-hidden">
          <div class="bg-flood2 px-4 py-2.5 border-b border-chalk font-semibold text-sm text-ink">Gia nhập ({{ count($transfersIn) }})</div>
          <div class="divide-y divide-chalk/70">
            @forelse($transfersIn as $tr)
              <div class="px-4 py-2.5 flex items-center justify-between text-xs">
                <a href="{{ !empty($tr['player']['id']) ? route('player.show', ['sport' => $sportKey, 'id' => $tr['player']['id']]) : '#' }}"
                   class="font-semibold text-ink hover:text-liveink">{{ $tr['player']['name'] ?? 'Cầu thủ' }}</a>
                <span class="fl-muted">{{ $tr['transferFrom']['name'] ?? '' }}</span>
              </div>
            @empty
              <div class="p-6 text-center text-xs fl-muted">Chưa có dữ liệu gia nhập.</div>
            @endforelse
          </div>
        </div>
        <div class="fl-field border border-chalk overflow-hidden">
          <div class="bg-flood2 px-4 py-2.5 border-b border-chalk font-semibold text-sm text-ink">Rời đội ({{ count($transfersOut) }})</div>
          <div class="divide-y divide-chalk/70">
            @forelse($transfersOut as $tr)
              <div class="px-4 py-2.5 flex items-center justify-between text-xs">
                <a href="{{ !empty($tr['player']['id']) ? route('player.show', ['sport' => $sportKey, 'id' => $tr['player']['id']]) : '#' }}"
                   class="font-semibold text-ink hover:text-liveink">{{ $tr['player']['name'] ?? 'Cầu thủ' }}</a>
                <span class="fl-muted">{{ $tr['transferTo']['name'] ?? '' }}</span>
              </div>
            @empty
              <div class="p-6 text-center text-xs fl-muted">Chưa có dữ liệu rời đội.</div>
            @endforelse
          </div>
        </div>
      </div>
    @endif
  </main>
</body>
</html>
