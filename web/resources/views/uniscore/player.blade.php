<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $playerData['player']['name'] ?? 'Hồ sơ cầu thủ' }} - {{ $siteSettings['site_name'] ?? 'UNISCORE' }}</title>
  @if(!empty($siteSettings['site_favicon_url']))
    <link rel="icon" href="{{ $siteSettings['site_favicon_url'] }}">
  @endif
  @include('partials.fl-head')
</head>
<body class="fl min-h-screen flex flex-col">
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
    $p = $playerData['player'] ?? [];
    $pName = $p['name'] ?? 'Cầu thủ';
    $team = $p['team'] ?? [];
    $teamId = $team['id'] ?? null;
    $country = $p['country']['name'] ?? '';
    $pos = $p['position'] ?? '';
    $shirt = $p['jerseyNumber'] ?? ($p['shirtNumber'] ?? '');
    $height = $p['height'] ?? null;
    $foot = $p['preferredFoot'] ?? '';
    $dobTs = $p['dateOfBirthTimestamp'] ?? null;
    $transfers = $playerData['transfer_history'] ?? [];
    $recentEvents = $playerData['recent_events'] ?? [];
  @endphp

  <!-- NAVY HERO -->
  <section class="bg-stand/70 border-b border-seat">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-6 py-6 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4 min-w-0">
        <img src="{{ $playerData['avatar'] }}" alt="{{ $pName }}"
             class="w-16 h-16 rounded-full object-cover bg-night border border-seat shrink-0"
             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($pName,0,2)) }}&background=163152&color=E8EEF6&size=64'">
        <div class="min-w-0">
          <div class="text-xs text-mist flex items-center gap-2">
            <span>{{ $sports[$sportKey]['icon'] ?? '' }} {{ $sports[$sportKey]['name'] ?? $sportKey }}</span>
            @if($teamId)
              <span>—</span>
              <a href="{{ route('team.show', ['sport' => $sportKey, 'id' => $teamId]) }}" class="text-live hover:underline font-semibold">
                {{ $team['name'] ?? 'Đội bóng' }}
              </a>
            @endif
          </div>
          <h1 class="fl-display text-3xl sm:text-4xl text-frost truncate">{{ $pName }}</h1>
          <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-mist mt-1">
            @if($country) <span>Quốc tịch: <b class="text-frost">{{ $country }}</b></span> @endif
            @if($pos) <span>Vị trí: <b class="text-frost">{{ $pos }}</b></span> @endif
            @if($shirt) <span>Số áo: <b class="text-live font-score">#{{ $shirt }}</b></span> @endif
            @if($height) <span>Chiều cao: <b class="text-frost fl-num">{{ $height }} cm</b></span> @endif
            @if($foot) <span>Chân thuận: <b class="text-frost">{{ $foot }}</b></span> @endif
            @if($dobTs) <span>Ngày sinh: <b class="text-frost fl-num">{{ date('d/m/Y', $dobTs) }}</b></span> @endif
          </div>
        </div>
      </div>
      @if($teamId)
        <a href="{{ route('team.show', ['sport' => $sportKey, 'id' => $teamId]) }}" class="fl-btn text-xs">Xem CLB {{ $team['name'] ?? '' }}</a>
      @else
        <a href="{{ route('home', ['sport' => $sportKey]) }}" class="fl-btn text-xs">← Trang chủ</a>
      @endif
    </div>
  </section>

  <main class="max-w-[1440px] w-full mx-auto px-4 lg:px-6 py-5 grid grid-cols-1 lg:grid-cols-3 gap-5 flex-1">
    <!-- Recent Matches -->
    <div class="lg:col-span-2 fl-field border border-chalk overflow-hidden">
      <div class="bg-flood2 px-4 py-2.5 border-b border-chalk flex justify-between items-center">
        <h2 class="text-sm font-semibold text-ink">Các trận đấu gần đây</h2>
        <span class="text-xs fl-muted font-score">{{ count($recentEvents) }} trận</span>
      </div>
      <div class="divide-y divide-chalk/70">
        @forelse($recentEvents as $ev)
          @php $ps = $ev['player_stats'] ?? []; @endphp
          <div class="px-4 py-2.5 flex items-center justify-between gap-2 text-xs">
            <span class="text-ink2 font-score w-20 shrink-0">{{ !empty($ev['startTimestamp']) ? date('d/m/y', $ev['startTimestamp']) : '' }}</span>
            <div class="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-2 min-w-0">
              <span class="font-medium text-ink truncate text-right">{{ $ev['homeTeam']['name'] ?? '' }}</span>
              <span class="font-score text-sm font-bold bg-white border border-chalk rounded px-2 py-0.5 text-ink">
                {{ $ev['homeScore']['display'] ?? ($ev['homeScore']['current'] ?? '-') }} - {{ $ev['awayScore']['display'] ?? ($ev['awayScore']['current'] ?? '-') }}
              </span>
              <span class="font-medium text-ink truncate">{{ $ev['awayTeam']['name'] ?? '' }}</span>
            </div>
            @if(!empty($ps['rating']))
              <span class="font-score text-sm font-bold px-2 py-0.5 rounded bg-live/20 text-liveink shrink-0">{{ number_format((float)$ps['rating'], 1) }}</span>
            @endif
          </div>
        @empty
          <div class="p-6 text-center text-xs fl-muted">Chưa có dữ liệu trận đấu gần đây.</div>
        @endforelse
      </div>
    </div>

    <!-- Transfer History -->
    <div class="fl-field border border-chalk overflow-hidden">
      <div class="bg-flood2 px-4 py-2.5 border-b border-chalk flex justify-between items-center">
        <h2 class="text-sm font-semibold text-ink">Lịch sử chuyển nhượng</h2>
        <span class="text-xs fl-muted font-score">{{ count($transfers) }}</span>
      </div>
      <div class="divide-y divide-chalk/70">
        @forelse($transfers as $tr)
          <div class="px-4 py-2.5 text-xs space-y-0.5">
            <div class="flex items-center justify-between">
              <span class="font-semibold text-ink">{{ $tr['transferFrom']['name'] ?? 'CLB cũ' }} → {{ $tr['transferTo']['name'] ?? 'CLB mới' }}</span>
              <span class="font-score text-liveink">{{ $tr['transferFeeDescription'] ?? '' }}</span>
            </div>
            @if(!empty($tr['transferDateTimestamp']))
              <div class="text-[11px] fl-muted">{{ date('d/m/Y', $tr['transferDateTimestamp']) }}</div>
            @endif
          </div>
        @empty
          <div class="p-6 text-center text-xs fl-muted">Chưa có lịch sử chuyển nhượng.</div>
        @endforelse
      </div>
    </div>
  </main>
</body>
</html>
