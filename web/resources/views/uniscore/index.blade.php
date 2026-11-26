<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $siteSettings['site_name'] ?? 'UNISCORE' }} - {{ $siteSettings['site_tagline'] ?? 'Tỷ số trực tiếp 10 môn thể thao' }}</title>
  @if(!empty($siteSettings['seo_description']))
    <meta name="description" content="{{ $siteSettings['seo_description'] }}">
  @endif
  @if(!empty($siteSettings['site_favicon_url']))
    <link rel="icon" href="{{ $siteSettings['site_favicon_url'] }}">
  @endif
  @include('partials.fl-head')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <style>
    @media (max-width: 1279px) {
      #rightDetailAside.is-mobile-open {
        display: flex !important;
        position: fixed;
        inset: 0;
        z-index: 60;
        width: 100% !important;
        max-height: 100vh !important;
        border-radius: 0 !important;
      }
    }
    @media (max-width: 1023px) {
      #leftLeaguesAside.is-mobile-open {
        display: flex !important;
        position: fixed;
        inset: 0;
        z-index: 55;
        width: 100% !important;
        max-width: 320px;
        max-height: 100vh !important;
        background: var(--night);
        padding: 16px;
        border-right: 1px solid var(--seat);
        overflow-y: auto;
      }
    }
  </style>
</head>
<body class="fl min-h-screen flex flex-col">

  <!-- TOP HEADER (FLOODLIGHT STADIUM CHROME) -->
  <header class="bg-stand border-b border-seat sticky top-0 z-50">
    <div class="max-w-[1520px] mx-auto px-3 lg:px-5 h-14 flex items-center justify-between gap-3">
      <div class="flex items-center gap-3 lg:gap-5 min-w-0">
        <button type="button" onclick="toggleMobileLeaguesDrawer()"
                class="lg:hidden fl-btn px-2.5 py-1.5 text-xs" aria-label="Mở danh sách giải đấu">
          ☰ Giải đấu
        </button>

        <a href="{{ route('home') }}" class="flex items-center gap-2 font-score font-extrabold text-2xl tracking-tight select-none shrink-0">
          @include('partials.fl-brand')
        </a>

        <!-- Search Bar -->
        <form action="{{ route('home') }}" method="GET" class="hidden sm:flex items-center relative">
          <input type="hidden" name="sport" value="{{ $sportKey }}">
          <input type="hidden" name="date" value="{{ $date }}">
          <input type="hidden" name="status" value="{{ $status }}">
          <input type="hidden" name="source" value="{{ $source }}">
          <input type="text" name="q" value="{{ $search }}" placeholder="Tìm đội bóng hoặc giải đấu..."
                 class="fl-input pl-3 pr-8 w-60 lg:w-72">
          <button type="submit" class="absolute right-2.5 text-mist hover:text-frost text-xs" aria-label="Tìm kiếm">🔍</button>
        </form>
      </div>

      <!-- Right Navigation -->
      <div class="flex items-center gap-2 sm:gap-2.5 text-[13px]">
        <div class="flex items-center gap-2 bg-night border border-seat px-3 py-1.5 rounded-[4px]">
          <span class="fl-live-dot"></span>
          <span class="text-mist font-medium">Live</span>
          <b id="globalLiveCount" class="font-score text-base text-live leading-none">{{ $liveCount }}</b>
        </div>
        @if(!empty($topLeagues[0]))
          <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $topLeagues[0]['id'], 'tab' => 'standings']) }}"
             class="hidden lg:inline-flex fl-btn">Bảng xếp hạng</a>
        @endif
        <a href="{{ route('admin.index') }}" class="fl-btn">
          Quản trị
        </a>
      </div>
    </div>

    <!-- HORIZONTAL SPORT TAB BAR -->
    <nav class="border-t border-seat/70 bg-night/50" aria-label="Chọn môn thể thao">
      <div class="max-w-[1520px] mx-auto px-3 lg:px-5 flex items-center gap-1 overflow-x-auto">
        @foreach($sports as $k => $info)
          <a href="{{ route('home', ['sport' => $k, 'date' => $date, 'status' => $status, 'source' => $source]) }}"
             class="fl-tab px-3 py-2.5 text-[13px] flex items-center gap-1.5 shrink-0 {{ $sportKey === $k ? 'is-active' : '' }}">
            <span>{{ $info['icon'] }}</span>
            <span>{{ $info['name'] }}</span>
          </a>
        @endforeach
      </div>
    </nav>
  </header>

  <!-- 3-COLUMN WORKSPACE: LEAGUES (NAVY) | FLOODLIT MATCH FIELD (LIGHT) | SCOREBOARD DETAIL (NAVY) -->
  <div class="flex-1 max-w-[1520px] w-full mx-auto px-3 lg:px-5 py-4 flex gap-4 items-start">

    <!-- LEFT SIDEBAR: GIẢI ĐẤU NỔI BẬT + TẤT CẢ GIẢI ĐẤU THEO QUỐC GIA -->
    <aside id="leftLeaguesAside" class="hidden lg:flex flex-col w-[248px] shrink-0 gap-3 sticky top-[112px] max-h-[calc(100vh-128px)] overflow-y-auto pr-0.5">
      <div class="flex lg:hidden items-center justify-between pb-2 border-b border-seat">
        <span class="font-semibold text-frost text-sm">Giải đấu</span>
        <button type="button" onclick="toggleMobileLeaguesDrawer()" class="fl-btn px-2 py-1 text-xs">Đóng</button>
      </div>

      <!-- CARD 1: GIẢI ĐẤU NỔI BẬT -->
      <div class="fl-panel p-3">
        <div class="flex items-center justify-between mb-2.5 pb-2 border-b border-seat">
          <h2 class="text-[13px] font-semibold text-frost">Giải đấu nổi bật</h2>
          <span class="text-xs text-live" aria-hidden="true">★</span>
        </div>
        <div class="space-y-0.5">
          @forelse($topLeagues as $league)
            <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $league['id']]) }}"
               class="flex items-center gap-2.5 px-2 py-1.5 rounded-[4px] hover:bg-stand2 transition group">
              <img src="{{ $league['logo'] }}" alt="{{ $league['name'] }}"
                   class="w-5 h-5 object-contain shrink-0 rounded-[4px] bg-night p-0.5"
                   onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($league['name'],0,2)) }}&background=163152&color=E8EEF6&size=32'">
              <span class="text-[13px] text-frost/90 group-hover:text-frost truncate">{{ $league['name'] }}</span>
            </a>
          @empty
            <div class="text-xs fl-muted py-2">Đang tải giải đấu nổi bật...</div>
          @endforelse
        </div>
      </div>

      <!-- CARD 2: TẤT CẢ GIẢI ĐẤU (THEO QUỐC GIA - ACCORDION) -->
      <div class="fl-panel p-3">
        <div class="flex items-center justify-between mb-2">
          <h2 class="text-[13px] font-semibold text-frost">Tất cả giải đấu</h2>
          <span class="text-xs fl-muted font-score">{{ count($categories) }} khu vực</span>
        </div>

        <!-- Country Filter Input -->
        <div class="mb-2.5">
          <input type="text" id="countrySearchInput" oninput="filterCountries()"
                 placeholder="Tìm quốc gia..."
                 class="fl-input w-full text-xs">
        </div>

        <div id="countriesAccordionList" class="space-y-0.5 max-h-[420px] overflow-y-auto pr-1">
          @foreach($categories as $cat)
            <div class="country-item" data-name="{{ strtolower($cat['name']) }}">
              <button type="button"
                      onclick="toggleCountry('{{ $sportKey }}', '{{ $cat['id'] }}', this)"
                      class="w-full flex items-center justify-between px-2 py-1.5 rounded-[4px] hover:bg-stand2 transition text-left">
                <div class="flex items-center gap-2 min-w-0">
                  <img src="{{ $cat['flag'] }}" alt="{{ $cat['name'] }}"
                       class="w-4 h-4 rounded-full object-cover shrink-0 bg-night"
                       onerror="this.src='https://img.uniscore.com/football/competition/international/image/small/dark'">
                  <span class="text-[13px] text-frost/90 truncate">{{ $cat['name'] }}</span>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                  @if(($cat['totalEvents'] ?? 0) > 0)
                    <span class="text-[11px] font-score px-1.5 py-0.5 rounded-[4px] bg-night text-mist">{{ $cat['totalEvents'] }}</span>
                  @endif
                  <span class="chevron text-[10px] fl-muted transition-transform">▼</span>
                </div>
              </button>
              <div class="leagues-drawer hidden pl-4 pr-1 py-1 space-y-1 border-l border-seat ml-3 mt-0.5"></div>
            </div>
          @endforeach
        </div>
      </div>

    </aside>

    <!-- CENTER COLUMN: CONTROLS (NAVY) + FLOODLIT MATCH FIELD (LIGHT) -->
    <main class="flex-1 min-w-0 space-y-3">

      @if(!empty($siteSettings['notice_banner']))
        <div class="fl-panel border-live/50 px-3.5 py-2 text-xs text-frost flex items-center gap-2">
          <span class="fl-live-dot shrink-0"></span>
          <span>{{ $siteSettings['notice_banner'] }}</span>
        </div>
      @endif

      <!-- AUTO-SYNC CACHE-THROUGH NOTIFICATION -->
      @if(($autoSyncedCount ?? 0) > 0)
        <div class="fl-panel border-goal/50 px-3.5 py-2 text-xs flex items-center justify-between text-frost">
          <span>Đã đồng bộ thêm <strong class="text-goal font-score text-sm">{{ $autoSyncedCount }}</strong> trận mới vào cơ sở dữ liệu.</span>
          <span class="text-xs fl-muted">{{ $dateFrom === $dateTo ? $dateFrom : "$dateFrom → $dateTo" }}</span>
        </div>
      @endif

      <!-- DATE STRIP + DATE RANGE PICKER + STATUS SEGMENTED CONTROL -->
      <div class="fl-panel p-3 space-y-3">
        <!-- 7-Day Date Strip + Interactive Range Calendar -->
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-1 overflow-x-auto">
            @php
              $dayNames = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
              $todayStr = date('Y-m-d');
            @endphp
            @for($offset = -3; $offset <= 3; $offset++)
              @php
                $ts = strtotime("{$offset} days");
                $dStr = date('Y-m-d', $ts);
                $isActive = ($dateFrom === $dStr && $dateTo === $dStr);
                $labelDay = ($dStr === $todayStr) ? 'Hôm nay' : $dayNames[(int)date('w', $ts)];
              @endphp
              <a href="{{ route('home', ['sport' => $sportKey, 'date_from' => $dStr, 'date_to' => $dStr, 'status' => $status, 'source' => $source, 'q' => $search]) }}"
                 class="flex flex-col items-center px-3 py-1 rounded-[4px] text-center min-w-[56px] transition {{ $isActive ? 'bg-frost text-ink font-semibold' : 'text-mist hover:bg-stand2 hover:text-frost' }}">
                <span class="text-[11px]">{{ $labelDay }}</span>
                <span class="text-xs font-score font-semibold">{{ date('d/m', $ts) }}</span>
              </a>
            @endfor
          </div>

          <!-- Interactive Date Range Picker (Từ ngày -> Đến ngày) -->
          <form id="dateRangeForm" action="{{ route('home') }}" method="GET" class="flex items-center gap-1.5 shrink-0">
            <input type="hidden" name="sport" value="{{ $sportKey }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="source" value="{{ $source }}">
            <input type="hidden" name="q" value="{{ $search }}">
            <input type="hidden" id="dateFromInput" name="date_from" value="{{ $dateFrom }}">
            <input type="hidden" id="dateToInput" name="date_to" value="{{ $dateTo }}">
            <input type="text" id="dateRangePicker" readonly
                   value="{{ $dateFrom === $dateTo ? $dateFrom : ($dateFrom . ' to ' . $dateTo) }}"
                   placeholder="Chọn khoảng ngày"
                   class="fl-input w-48 cursor-pointer text-xs">
            <button type="submit" class="fl-btn fl-btn--primary text-xs">Lọc</button>
          </form>
        </div>

        <!-- Status Filter Segmented Control -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-2.5 border-t border-seat">
          <div class="fl-seg overflow-x-auto">
            <a href="{{ route('home', array_merge(request()->query(), ['status' => 'all'])) }}"
               class="{{ $status === 'all' ? 'is-active' : '' }}">Tất cả</a>
            <a href="{{ route('home', array_merge(request()->query(), ['status' => 'live'])) }}"
               class="inline-flex items-center gap-1.5 {{ $status === 'live' ? 'is-active' : '' }}">
              <span class="fl-live-dot"></span> Live
            </a>
            <a href="{{ route('home', array_merge(request()->query(), ['status' => 'upcoming'])) }}"
               class="{{ $status === 'upcoming' ? 'is-active' : '' }}">Sắp diễn ra</a>
            <a href="{{ route('home', array_merge(request()->query(), ['status' => 'finished'])) }}"
               class="{{ $status === 'finished' ? 'is-active' : '' }}">Kết thúc</a>
          </div>

          <div class="text-xs fl-muted">
            Tổng <b class="font-score text-sm text-frost">{{ $grouped->flatten()->count() }}</b> trận
          </div>
        </div>
      </div>

      <!-- FLOODLIT MATCH FIELD -->
      @if($grouped->isEmpty())
        <div class="fl-field border border-chalk p-10 text-center space-y-3">
          <div class="text-4xl">{{ $sports[$sportKey]['icon'] ?? '⚽' }}</div>
          <h3 class="font-display text-2xl text-ink">Chưa có trận đấu cho ngày {{ date('d/m/Y', strtotime($date)) }}</h3>
          <p class="text-[13px] fl-muted max-w-md mx-auto">
            Chọn một giải đấu ở cột <strong>Giải đấu nổi bật</strong> bên trái để xem lịch đấu và bảng xếp hạng, hoặc vào <a href="{{ route('admin.index') }}" class="text-liveink font-semibold underline">Quản trị</a> để tải thêm dữ liệu.
          </p>
        </div>
      @else
        <div class="fl-field border border-chalk overflow-hidden divide-y divide-chalk">
          @foreach($grouped as $tourName => $matches)
            @php
              $firstM = $matches->first();
              $tourId = $firstM->tournament_id;
              $tourLogo = $firstM->tournament_logo;
              $tourRound = $firstM->payload['taxonomy']['tournament_league']['round'] ?? '';
            @endphp
            <section>
              <!-- Tournament Header -->
              <div class="bg-flood2 px-3.5 py-2 border-b border-chalk flex items-center justify-between">
                @if($tourId)
                  <a href="{{ route('competition.show', ['sport' => $sportKey, 'id' => $tourId]) }}"
                     class="flex items-center gap-2.5 group min-w-0">
                    <img src="{{ $tourLogo }}" alt="{{ $tourName }}"
                         class="w-5 h-5 object-contain rounded-[4px] bg-white p-0.5 border border-chalk"
                         onerror="this.style.display='none'">
                    <div class="min-w-0">
                      <div class="text-[13px] font-semibold text-ink group-hover:text-liveink truncate">{{ $tourName }}</div>
                      <div class="text-[11px] fl-muted truncate">
                        {{ $firstM->tournament_country ?: 'Quốc tế' }}{{ $tourRound ? ' — Vòng ' . $tourRound : '' }}
                      </div>
                    </div>
                  </a>
                @else
                  <div class="flex items-center gap-2.5 min-w-0">
                    <span class="text-base">🏆</span>
                    <div class="min-w-0">
                      <div class="text-[13px] font-semibold text-ink truncate">{{ $tourName }}</div>
                      <div class="text-[11px] fl-muted truncate">{{ $firstM->tournament_country ?: 'Quốc tế' }}</div>
                    </div>
                  </div>
                @endif
                <span class="text-xs fl-muted font-score shrink-0">{{ count($matches) }} trận</span>
              </div>

              <!-- Match Rows on Floodlit Surface -->
              <div class="divide-y divide-chalk/70">
                @foreach($matches as $m)
                  @php
                    $isLive = str_contains($m->state_category, 'Live');
                    $isFinished = str_contains($m->state_category, 'Finished');
                    $stCode = $m->payload['match_timeline']['status_code'] ?? 0;
                    $pStartTs = $m->payload['match_timeline']['readtime']['period_start_ts'] ?? 0;
                    $timeLabel = $m->start_time_iso ? date('H:i', strtotime($m->start_time_iso)) : '--:--';
                    if ($isLive) {
                        $timeLabel = ($m->readtime && !preg_match('/^\d{8,}$/', $m->readtime)) ? $m->readtime : 'Live';
                    } elseif ($isFinished) {
                        $timeLabel = 'FT';
                    }
                  @endphp
                  <div id="match-row-{{ $m->id }}"
                       data-external-id="{{ $m->external_id }}"
                       data-is-live="{{ $isLive ? '1' : '0' }}"
                       data-status-code="{{ $stCode }}"
                       data-period-start-ts="{{ $pStartTs }}"
                       onclick='selectMatchForRightPanel(@json($m), this)'
                       class="match-row px-3.5 py-2.5 hover:bg-flood2/80 transition-colors cursor-pointer flex items-center justify-between gap-2 {{ $isLive ? 'bg-[#FFF9EB]' : 'bg-flood' }} {{ ($defaultMatch && $defaultMatch->id === $m->id) ? 'match-row-active' : '' }}">

                    <!-- Left: Time / Live Minute / FT -->
                    <div class="w-16 shrink-0 flex flex-col items-center justify-center leading-tight">
                      @if($isLive)
                        <span class="live-badge-pill inline-flex items-center gap-1 px-1.5 py-0.5 mb-0.5 rounded-[4px] bg-live/20 text-[10px] font-semibold text-liveink">
                          <span class="fl-live-dot" style="width:5px;height:5px;"></span>
                          Live
                        </span>
                      @endif
                      <span class="live-clock-cell font-score text-sm font-bold {{ $isLive ? 'text-liveink' : ($isFinished ? 'text-ink2' : 'text-ink') }}">
                        {{ $timeLabel }}
                      </span>
                    </div>

                    <!-- Center: Home [Logo] [Score] [Logo] Away -->
                    <div class="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-2.5 min-w-0">
                      <div class="flex items-center justify-end gap-2 min-w-0">
                        <span class="text-[13px] font-medium text-ink truncate text-right">{{ $m->home_team }}</span>
                        <img src="{{ $m->home_logo }}" alt="{{ $m->home_team }}"
                             class="w-5 h-5 object-contain shrink-0 rounded-[4px]"
                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($m->home_team,0,2)) }}&background=D3DCE7&color=0C1A2C&size=32'">
                      </div>

                      <div class="live-score-cell font-score text-lg font-bold px-2.5 py-0.5 rounded-[4px] min-w-[58px] text-center border transition-colors {{ $isLive ? 'bg-[#FFF2D1] border-live text-liveink' : 'bg-white border-chalk text-ink' }}">
                        @if($m->home_score !== null && $m->away_score !== null)
                          {{ $m->home_score }} - {{ $m->away_score }}
                        @else
                          {{ $m->start_time_iso ? date('H:i', strtotime($m->start_time_iso)) : 'vs' }}
                        @endif
                      </div>

                      <div class="flex items-center justify-start gap-2 min-w-0">
                        <img src="{{ $m->away_logo }}" alt="{{ $m->away_team }}"
                             class="w-5 h-5 object-contain shrink-0 rounded-[4px]"
                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($m->away_team,0,2)) }}&background=D3DCE7&color=0C1A2C&size=32'">
                        <span class="text-[13px] font-medium text-ink truncate">{{ $m->away_team }}</span>
                      </div>
                    </div>

                    <!-- Right: Follow Match Star (localStorage) -->
                    <div class="w-7 shrink-0 flex justify-end" onclick="event.stopPropagation()">
                      <button type="button"
                              data-follow-match-id="{{ $m->id }}"
                              onclick="toggleFollowMatch({{ $m->id }}, this)"
                              title="Theo dõi trận đấu"
                              class="text-sm transition-colors {{ $m->is_pinned ? 'text-live' : 'text-ink2/35 hover:text-ink2' }}">★</button>
                    </div>
                  </div>
                @endforeach
              </div>
            </section>
          @endforeach
        </div>
      @endif

    </main>

    <!-- RIGHT SCOREBOARD PANEL (NAVY) -->
    <aside id="rightDetailAside" class="hidden xl:flex flex-col w-[390px] shrink-0 fl-panel overflow-hidden sticky top-[112px] max-h-[calc(100vh-128px)]" style="border-radius: var(--r-lg);">
      <div class="flex xl:hidden items-center justify-between px-4 py-2.5 bg-night border-b border-seat">
        <span class="text-sm font-semibold text-frost">Chi tiết trận đấu</span>
        <button type="button" onclick="closeMobileRightPanel()" class="fl-btn px-2.5 py-1 text-xs">Đóng</button>
      </div>

      <div id="rightPanelEmpty" class="p-8 text-center text-xs fl-muted {{ $defaultMatch ? 'hidden' : '' }}">
        <div class="text-3xl mb-2">⚽</div>
        Chọn một trận đấu ở danh sách giữa để xem chi tiết, thống kê và đội hình.
      </div>

      <div id="rightPanelContent" class="flex flex-col h-full overflow-hidden {{ $defaultMatch ? '' : 'hidden' }}">
        <!-- Hero Scoreboard -->
        <div class="bg-stand2/60 p-4 border-b border-seat">
          <div class="flex items-center justify-between gap-2 text-xs fl-muted mb-3">
            <a id="rpTourLink" href="#" class="flex items-center gap-1.5 hover:text-live truncate">
              <span id="rpTourName" class="font-semibold text-frost truncate">Giải đấu</span>
            </a>
            <span id="rpSourceBadge" class="text-[11px] px-2 py-0.5 rounded-[4px] bg-night border border-seat text-mist shrink-0">UniScore</span>
          </div>

          <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 text-center">
            <div class="flex flex-col items-center">
              <img id="rpHomeLogo" src="" class="w-12 h-12 object-contain mb-1.5 rounded-[8px] bg-night p-1 border border-seat">
              <div id="rpHomeName" class="text-[13px] font-semibold text-frost line-clamp-2">Home</div>
            </div>

            <div class="px-2">
              <div id="rpLiveBadge" class="hidden inline-flex items-center gap-1.5 px-2.5 py-0.5 mb-1.5 rounded-[4px] bg-live/20 border border-live/50 text-[11px] font-semibold text-live">
                <span class="fl-live-dot" style="width:6px;height:6px;"></span>
                Đang diễn ra
              </div>
              <div id="rpScoreBox" class="font-score text-4xl font-extrabold text-frost tracking-wide bg-night border border-seat rounded-[8px] px-4 py-1">
                0 - 0
              </div>
              <div id="rpStatusLabel" class="font-score text-sm font-bold text-mist mt-1">Upcoming</div>
            </div>

            <div class="flex flex-col items-center">
              <img id="rpAwayLogo" src="" class="w-12 h-12 object-contain mb-1.5 rounded-[8px] bg-night p-1 border border-seat">
              <div id="rpAwayName" class="text-[13px] font-semibold text-frost line-clamp-2">Away</div>
            </div>
          </div>

          <div id="rpMetaInfo" class="mt-3 pt-2 border-t border-seat/70 flex items-center justify-center gap-4 text-[11px] fl-muted">
            <span id="rpVenue">🏟️ Sân vận động</span>
            <span id="rpReferee">🧑‍⚖️ Trọng tài</span>
          </div>
        </div>

        <!-- Tabs -->
        <div class="flex flex-wrap border-b border-seat bg-night/70 px-2 pt-1 gap-x-1 gap-y-0.5 text-xs font-semibold">
          <button onclick="switchRpTab('details')" id="rpTabBtn-details" class="fl-tab is-active px-2.5 py-2 shrink-0">Chi tiết</button>
          <button onclick="switchRpTab('lineups')" id="rpTabBtn-lineups" class="fl-tab px-2.5 py-2 shrink-0">Đội hình</button>
          <button onclick="switchRpTab('stats')" id="rpTabBtn-stats" class="fl-tab px-2.5 py-2 shrink-0">Thống kê</button>
          <button onclick="switchRpTab('commentary')" id="rpTabBtn-commentary" class="fl-tab px-2.5 py-2 shrink-0">Tường thuật</button>
          <button onclick="switchRpTab('h2h')" id="rpTabBtn-h2h" class="fl-tab px-2.5 py-2 shrink-0">Đối đầu</button>
          <button onclick="switchRpTab('analytics')" id="rpTabBtn-analytics" class="fl-tab px-2.5 py-2 shrink-0">Phân tích AI</button>
          <button onclick="switchRpTab('standings')" id="rpTabBtn-standings" class="fl-tab px-2.5 py-2 shrink-0">BXH</button>
          <button onclick="switchRpTab('bracket')" id="rpTabBtn-bracket" class="fl-tab px-2.5 py-2 shrink-0">Nhánh đấu</button>
          <button onclick="switchRpTab('json')" id="rpTabBtn-json" class="fl-tab px-2.5 py-2 shrink-0">JSON</button>
        </div>

        <!-- Tab Body (Scrollable) -->
        <div class="flex-1 overflow-y-auto p-3.5 text-xs">
          <div id="rpLoadingBar" class="hidden mb-3 text-center text-xs text-mist bg-stand2 border border-seat rounded-[4px] py-1.5">
            Đang tải chi tiết trận đấu...
          </div>
          <div id="rpTab-details" class="space-y-3"></div>
          <div id="rpTab-lineups" class="hidden space-y-3"></div>
          <div id="rpTab-stats" class="hidden space-y-3"></div>
          <div id="rpTab-commentary" class="hidden space-y-3"></div>
          <div id="rpTab-h2h" class="hidden space-y-3"></div>
          <div id="rpTab-analytics" class="hidden space-y-3"></div>
          <div id="rpTab-standings" class="hidden space-y-2"></div>
          <div id="rpTab-bracket" class="hidden space-y-3"></div>
          <div id="rpTab-json" class="hidden">
            <pre id="rpJsonPre" class="bg-night p-3 rounded-[4px] border border-seat text-[11px] text-frost/90 font-mono overflow-x-auto"></pre>
          </div>
        </div>
      </div>
    </aside>

  </div>

  <footer class="border-t border-seat bg-stand/80 text-xs text-mist py-3 px-4 mt-4">
    <div class="max-w-[1520px] mx-auto flex flex-wrap items-center justify-between gap-2">
      <div>
        <strong class="text-frost">{{ $siteSettings['site_name'] ?? 'UNISCORE' }}</strong>
        @if(!empty($siteSettings['footer_text']))
          <span>— {{ $siteSettings['footer_text'] }}</span>
        @endif
      </div>
      <div class="flex items-center gap-4">
        @if(!empty($siteSettings['contact_email']))
          <a href="mailto:{{ $siteSettings['contact_email'] }}" class="hover:text-frost">{{ $siteSettings['contact_email'] }}</a>
        @endif
        @if(!empty($siteSettings['support_url']))
          <a href="{{ $siteSettings['support_url'] }}" target="_blank" class="hover:text-live">Hỗ trợ</a>
        @endif
      </div>
    </div>
  </footer>

  <script>
    // -------------------------------------------------------------------------
    // 1. SIDEBAR: FILTER COUNTRIES & ACCORDION EXPAND LEAGUES
    // -------------------------------------------------------------------------
    function filterCountries() {
      const q = document.getElementById('countrySearchInput').value.toLowerCase().trim();
      document.querySelectorAll('#countriesAccordionList .country-item').forEach(el => {
        const name = el.getAttribute('data-name') || '';
        el.style.display = name.includes(q) ? '' : 'none';
      });
    }

    async function toggleCountry(sportKey, categoryId, btn) {
      const container = btn.parentElement;
      const drawer = container.querySelector('.leagues-drawer');
      const chevron = btn.querySelector('.chevron');

      if (!drawer.classList.contains('hidden')) {
        drawer.classList.add('hidden');
        chevron.style.transform = 'rotate(0deg)';
        return;
      }

      drawer.classList.remove('hidden');
      chevron.style.transform = 'rotate(180deg)';

      if (drawer.dataset.loaded === '1') return;

      drawer.innerHTML = '<div class="text-[11px] text-mist py-1">Đang tải giải đấu...</div>';
      try {
        const res = await fetch(`/api/leagues/${sportKey}/${categoryId}`);
        const data = await res.json();
        const leagues = data.leagues || [];
        drawer.dataset.loaded = '1';

        if (leagues.length === 0) {
          drawer.innerHTML = '<div class="text-[11px] text-mist py-1">Không có giải đấu nào.</div>';
          return;
        }

        drawer.innerHTML = leagues.map(lg => `
          <a href="/competition/${sportKey}/${lg.id}"
             class="flex items-center gap-2 px-2 py-1 rounded hover:bg-[#163152] text-[11px] text-frost/90 hover:text-[#FFB020] transition">
            <img src="${lg.logo}" class="w-4 h-4 object-contain shrink-0 rounded-sm bg-[#10253F]"
                 onerror="this.style.display='none'">
            <span class="truncate">${lg.name}</span>
          </a>
        `).join('');
      } catch (err) {
        drawer.innerHTML = '<div class="text-[11px] text-red-400 py-1">Lỗi tải danh sách giải.</div>';
      }
    }

    // -------------------------------------------------------------------------
    // 2. RIGHT STICKY MATCH DETAIL PANEL (CACHE-THROUGH LIVE HYDRATION!)
    // -------------------------------------------------------------------------
    let currentRightMatch = @json($defaultMatch);
    let activeFetchToken = 0;

    const STAT_LABELS_VI = {
      'xg_expected_goals': 'Bàn thắng kỳ vọng (xG)',
      'ball_possession': 'Tỷ lệ kiểm soát bóng',
      'shots': 'Tổng số cú sút',
      'shots_on_target': 'Cú sút trúng mục tiêu',
      'shots_off_target': 'Sút ra ngoài',
      'blocked_shots': 'Cú sút bị chặn',
      'corner_kicks': 'Phạt góc',
      'offsides': 'Việt vị',
      'fouls': 'Phạm lỗi',
      'yellow_cards': 'Thẻ vàng',
      'red_cards': 'Thẻ đỏ',
      'goalkeeper_saves': 'Cứu thua thủ môn',
      'dangerous_attacks': 'Tình huống tấn công nguy hiểm',
      'attacks': 'Tấn công',
      'key_passes': 'Đường chuyền quan trọng',
      'passes': 'Tổng đường chuyền',
      'accurate_passes': 'Chuyền chính xác',
      'big_chances': 'Cơ hội lớn',
      'tackles': 'Tắc bóng',
      'interceptions': 'Cắt bóng',
      'clearances': 'Phá bóng',
      'freekicks': 'Đá phạt',
      'free_kicks': 'Đá phạt',
      'saves': 'Cứu thua thủ môn',
      'throw_in': 'Ném biên',
      'long_balls_accuracy': 'Chuyền dài chính xác',
      'crosses_accuracy': 'Tạt bóng chính xác',
      'dribble': 'Rê bóng qua người',
      'dribbles': 'Rê bóng qua người',
      'duels_won': 'Tranh chấp thắng',
      'aerials_won': 'Không chiến thắng',
      'passes_accuracy': 'Tỷ lệ chuyền chính xác',
      // Basketball stats
      'field_goals': 'Ném rổ thành công (FG)',
      'two_pointers': 'Ném 2 điểm (2PT)',
      'three_pointers': 'Ném 3 điểm (3PT)',
      'free_throws': 'Ném phạt (FT)',
      'rebounds': 'Tổng bắt bóng bật bảng (REB)',
      'defensive_rebounds': 'Rebound phòng ngự (DREB)',
      'offensive_rebounds': 'Rebound tấn công (OREB)',
      'assists': 'Kiến tạo (AST)',
      'turnovers': 'Mất bóng (TOV)',
      'steals': 'Cướp bóng (STL)',
      'blocks': 'Chắn bóng (BLK)',
      'max_points_in_a_row': 'Chuỗi điểm liên tiếp tối đa',
      'biggest_lead': 'Cách biệt dẫn điểm lớn nhất',
      'time_spent_in_lead': 'Thời gian dẫn điểm',
      // Tennis & Racket sports stats
      'aces': 'Giao bóng ăn điểm trực tiếp (Aces)',
      'double_faults': 'Lỗi giao bóng kép (Double Faults)',
      'first_serve_percentage': 'Tỷ lệ giao bóng 1 vào sân (%)',
      'first_serve_points_won': 'Điểm thắng giao bóng 1',
      'second_serve_points_won': 'Điểm thắng giao bóng 2',
      'break_points_converted': 'Bẻ giao bóng thành công (Break Points)',
      'winners': 'Điểm đánh thắng trực tiếp (Winners)',
      'unforced_errors': 'Lỗi tự đánh hỏng',
      'total_points_won': 'Tổng số điểm thắng',
      // American Football, Baseball, Hockey, Table Tennis, Badminton stats
      'first_downs': 'Số lần First Downs',
      'third_down_conversion': 'Chuyển đổi 3rd Down thành công',
      'third_down_efficiency': 'Hiệu suất 3rd Down',
      'fourth_down_conversion': 'Chuyển đổi 4th Down thành công',
      'total_yards': 'Tổng số Yards tiến lên',
      'passing_yards': 'Yards ném bóng (Passing)',
      'rushing_yards': 'Yards chạy bóng (Rushing)',
      'penalties': 'Lỗi phạt (Penalties)',
      'penalty_minutes': 'Phút phạt ngồi ngoài (PIM)',
      'fumbles_lost': 'Mất bóng (Fumbles)',
      'interceptions_thrown': 'Bị cắt bóng (INT)',
      'hits': 'Đánh trúng bóng (Hits)',
      'errors': 'Lỗi phòng ngự (Errors)',
      'points_won': 'Tổng điểm thắng',
      'comebacks': 'Lội ngược dòng thắng',
      'service_points_won': 'Điểm thắng khi giao bóng',
      'receiver_points_won': 'Điểm thắng khi đỡ bóng',
      'service_errors': 'Lỗi giao bóng',
      'shots_on_goal': 'Sút trúng khung thành (SOG)',
      'power_play_goals': 'Bàn thắng hơn người (PPG)',
      'short_handed_goals': 'Bàn thắng thiếu người (SHG)',
      'faceoffs_won': 'Thắng tranh bóng (Faceoffs)'
    };

    function formatStatName(raw) {
      if (!raw) return '';
      const k = String(raw).toLowerCase().trim();
      if (STAT_LABELS_VI[k]) return STAT_LABELS_VI[k];
      return raw.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    let cachedExtendedData = null;
    let cachedLiveIncidents = [];
    let commentaryFilter = 'all';

    function toggleMobileLeaguesDrawer() {
      const aside = document.getElementById('leftLeaguesAside');
      if (aside) aside.classList.toggle('is-mobile-open');
    }

    function getFollowedMatchIds() {
      try {
        return JSON.parse(localStorage.getItem('uniscore_followed_matches') || '[]').map(String);
      } catch (e) {
        return [];
      }
    }
    function toggleFollowMatch(matchId, btn) {
      const id = String(matchId);
      const list = new Set(getFollowedMatchIds());
      if (list.has(id)) {
        list.delete(id);
        btn.classList.remove('text-live');
        btn.classList.add('text-ink2/35');
      } else {
        list.add(id);
        btn.classList.remove('text-ink2/35');
        btn.classList.add('text-live');
      }
      try { localStorage.setItem('uniscore_followed_matches', JSON.stringify([...list])); } catch (e) {}
    }
    (function hydrateFollowedStars() {
      const list = new Set(getFollowedMatchIds());
      document.querySelectorAll('[data-follow-match-id]').forEach(btn => {
        if (list.has(String(btn.dataset.followMatchId))) {
          btn.classList.remove('text-ink2/35');
          btn.classList.add('text-live');
        }
      });
    })();
    function closeMobileRightPanel() {
      const aside = document.getElementById('rightDetailAside');
      if (aside) aside.classList.remove('is-mobile-open');
    }
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 1280) {
        document.getElementById('rightDetailAside')?.classList.remove('is-mobile-open');
      }
      if (window.innerWidth >= 1024) {
        document.getElementById('leftLeaguesAside')?.classList.remove('is-mobile-open');
      }
    });

    function switchRpTab(tab) {
      ['details', 'lineups', 'stats', 'commentary', 'h2h', 'analytics', 'standings', 'bracket', 'json'].forEach(t => {
        const el = document.getElementById(`rpTab-${t}`);
        const btn = document.getElementById(`rpTabBtn-${t}`);
        if (el) el.classList.add('hidden');
        if (btn) btn.classList.remove('is-active');
      });
      const activeEl = document.getElementById(`rpTab-${tab}`);
      const activeBtn = document.getElementById(`rpTabBtn-${tab}`);
      if (activeEl) activeEl.classList.remove('hidden');
      if (activeBtn) activeBtn.classList.add('is-active');
    }

    async function selectMatchForRightPanel(match, rowEl) {
      if (!match) return;
      currentRightMatch = match;
      const myToken = ++activeFetchToken;

      document.querySelectorAll('[id^="match-row-"]').forEach(r => r.classList.remove('match-row-active'));
      if (rowEl) rowEl.classList.add('match-row-active');

      document.getElementById('rightPanelEmpty').classList.add('hidden');
      document.getElementById('rightPanelContent').classList.remove('hidden');
      if (rowEl && window.innerWidth < 1280 && !window.__initialLoad) {
        document.getElementById('rightDetailAside')?.classList.add('is-mobile-open');
      }

      const payload = match.payload || {};
      const tax = payload.taxonomy || {};
      const tour = tax.tournament_league || {};
      const ent = payload.entities || {};
      const teams = ent.teams_or_players || {};
      const timeline = payload.match_timeline || {};
      const scores = timeline.scores || {};
      const center = payload.match_center || {};

      // Immediate Header Render
      document.getElementById('rpTourName').innerText = match.tournament_name || tour.name || 'Giải đấu';
      const tourId = match.tournament_id || tour.id;
      document.getElementById('rpTourLink').href = tourId ? `/competition/${match.sport_key}/${tourId}` : '#';
      document.getElementById('rpSourceBadge').innerText = 'UNISCORE';

      document.getElementById('rpHomeName').innerText = match.home_team;
      document.getElementById('rpAwayName').innerText = match.away_team;
      document.getElementById('rpHomeLogo').src = match.home_logo || `https://ui-avatars.com/api/?name=${encodeURIComponent(match.home_team)}&background=1e2330&color=fff`;
      document.getElementById('rpAwayLogo').src = match.away_logo || `https://ui-avatars.com/api/?name=${encodeURIComponent(match.away_team)}&background=1e2330&color=fff`;

      const hScore = (match.home_score !== null && match.home_score !== undefined) ? match.home_score : '-';
      const aScore = (match.away_score !== null && match.away_score !== undefined) ? match.away_score : '-';
      document.getElementById('rpScoreBox').innerText = `${hScore} - ${aScore}`;

      const isLive = String(match.state_category).includes('Live');
      const rpLiveBadge = document.getElementById('rpLiveBadge');
      if (rpLiveBadge) {
        rpLiveBadge.classList.toggle('hidden', !isLive);
      }
      let stText = match.state_category;
      if (isLive) {
        stText = (match.readtime && !/^\d{8,}$/.test(match.readtime)) ? match.readtime : 'ĐANG DIỄN RA';
      }
      document.getElementById('rpStatusLabel').innerText = stText;
      document.getElementById('rpStatusLabel').className = isLive ? 'text-[11px] font-score font-bold text-[#2FBF71] mt-1' : 'text-[11px] font-bold text-mist mt-1';

      const venue = ent.venue || {};
      const officials = ent.match_officials || [];
      document.getElementById('rpVenue').innerText = venue.name ? `🏟️ ${venue.name}` : '🏟️ Đang cập nhật sân';
      document.getElementById('rpReferee').innerText = officials.length ? `🧑‍⚖️ ${officials[0].name}` : '🧑‍⚖️ Trọng tài';

      // Initial render from local payload
      renderRightDetails(center.live_events_summary || [], center.match_statistics || [], scores, match);
      renderRightStats(center.match_statistics || []);
      renderRightLineups(ent.squad_roster || {}, center.confirmed_lineups || {}, match);
      renderRightStandings([], teams.home?.id, teams.away?.id);
      document.getElementById('rpJsonPre').innerText = JSON.stringify(payload, null, 2);

      // Live / DB-First Hydration from /api/match-detail/{sport}/{external_id}
      const badgeEl = document.getElementById('rpSourceBadge');
      if (match.external_id && !String(match.external_id).startsWith('manual_')) {
        // Fast path: if Finished match already has detail_cached embedded from SQLite, render in 0ms without even calling AJAX!
        const isFin = String(match.state_category).includes('Finished');
        if (isFin && payload.detail_cached && payload.extended_cached) {
          if (badgeEl) {
            badgeEl.innerText = '💾 TỪ CSDL';
            badgeEl.className = 'text-[10px] px-1.5 py-0.5 rounded bg-emerald-950/70 text-emerald-400 border border-emerald-800/70 font-bold';
          }
          const dc = payload.detail_cached;
          const ec = payload.extended_cached;
          cachedLiveIncidents = dc.incidents || [];
          cachedExtendedData = ec;
          let mScores = scores || {};
          if (dc.event && dc.event.scores) {
            mScores = { ...mScores, period_breakdown: dc.event.scores, extra_scores: dc.event.extraScores || mScores.extra_scores || null };
          } else if (dc.event && (dc.event.homeScore || dc.event.awayScore)) {
            mScores = { ...mScores, period_breakdown: { home: dc.event.homeScore, away: dc.event.awayScore } };
          }
          renderRightDetails(dc.incidents || [], dc.statistics || [], mScores, match, dc.cricket_innings || []);
          renderRightStats(dc.statistics || []);
          renderRightCommentary(dc.incidents || [], match, dc.cricket_innings || []);
          if (dc.lineups) {
            renderRightLineups(
              { home: dc.lineups.home?.players || [], away: dc.lineups.away?.players || [] },
              { home_formation: dc.lineups.home?.formation || '', away_formation: dc.lineups.away?.formation || '' },
              match
            );
          }
          renderRightStandings(dc.standings || [], teams.home?.id || '', teams.away?.id || '');
          renderRightH2H(ec, match);
          renderRightAnalytics(ec, dc.statistics || [], match);
          renderRightBracket(ec.bracket, match);
          document.getElementById('rpJsonPre').innerText = JSON.stringify(dc, null, 2);
          return;
        }

        const loader = document.getElementById('rpLoadingBar');
        loader.classList.remove('hidden');
        try {
          const hId = teams.home?.id || '';
          const aId = teams.away?.id || '';
          const url = `/api/match-detail/${match.sport_key}/${match.external_id}?home_id=${encodeURIComponent(hId)}&away_id=${encodeURIComponent(aId)}`;
          const res = await fetch(url);
          const liveData = await res.json();
          if (myToken !== activeFetchToken) return; // User clicked another match

          // Show honest data source badge (💾 TỪ CSDL vs 🌐 ĐỒNG BỘ TỪ NGUỒN)
          if (badgeEl) {
            if (liveData._from_db) {
              badgeEl.innerText = '💾 TỪ CSDL';
              badgeEl.className = 'text-[10px] px-1.5 py-0.5 rounded bg-emerald-950/70 text-emerald-400 border border-emerald-800/70 font-bold';
            } else {
              badgeEl.innerText = '🌐 ĐỒNG BỘ NGUỒN';
              badgeEl.className = 'text-[10px] px-1.5 py-0.5 rounded bg-blue-950/70 text-blue-300 border border-blue-800/70 font-bold';
            }
          }

          // Update live score, venue, referee if present
          if (liveData.event) {
            const ev = liveData.event;
            const hs = ev.homeScore?.display ?? ev.homeScore?.current;
            const as = ev.awayScore?.display ?? ev.awayScore?.current;
            if (hs !== undefined && as !== undefined) {
              document.getElementById('rpScoreBox').innerText = `${hs} - ${as}`;
            }
            if (ev.venue?.name) document.getElementById('rpVenue').innerText = `🏟️ ${ev.venue.name}`;
            if (ev.referee?.name) document.getElementById('rpReferee').innerText = `🧑‍⚖️ ${ev.referee.name}`;
            if (ev.tournament?.id) {
              document.getElementById('rpTourLink').href = `/competition/${match.sport_key}/${ev.tournament.id}`;
            }
          }

          const liveIncidents = liveData.incidents || [];
          const liveStats = liveData.statistics || [];
          const liveLineups = liveData.lineups || null;
          const liveStandings = liveData.standings || [];
          const liveCricketInnings = liveData.cricket_innings || [];
          cachedLiveIncidents = liveIncidents;

          // Build accurate merged scores preserving compact `scores`/`extraScores` for all 10 sports!
          let mergedScores = scores || {};
          if (liveData.event) {
            if (liveData.event.scores && typeof liveData.event.scores === 'object') {
              mergedScores = {
                ...mergedScores,
                period_breakdown: liveData.event.scores,
                extra_scores: liveData.event.extraScores || mergedScores.extra_scores || null,
              };
            } else if (liveData.event.homeScore || liveData.event.awayScore) {
              mergedScores = {
                ...mergedScores,
                period_breakdown: { home: liveData.event.homeScore, away: liveData.event.awayScore },
              };
            }
          }

          renderRightDetails(liveIncidents, liveStats, mergedScores, match, liveCricketInnings);
          renderRightStats(liveStats);
          renderRightCommentary(liveIncidents, match, liveCricketInnings);
          if (liveLineups) {
            renderRightLineups(
              { home: liveLineups.home?.players || [], away: liveLineups.away?.players || [] },
              { home_formation: liveLineups.home?.formation || '', away_formation: liveLineups.away?.formation || '' },
              match
            );
          }
          renderRightStandings(liveStandings, hId, aId);
          document.getElementById('rpJsonPre').innerText = JSON.stringify(liveData, null, 2);

          // Also fetch Extended Tabs (H2H, Analytics, AI Prediction, Bracket) in parallel!
          const compId = liveData.event?.tournament?.id || tourId || '';
          const seasonId = liveData.event?.season?.id || liveData.event?.tournament?.season_id || '';
          fetch(`/api/match-extended/${match.sport_key}/${match.external_id}?home_id=${encodeURIComponent(hId)}&away_id=${encodeURIComponent(aId)}&comp_id=${encodeURIComponent(compId)}&season_id=${encodeURIComponent(seasonId)}`)
            .then(r => r.json())
            .then(ext => {
              if (myToken !== activeFetchToken) return;
              cachedExtendedData = ext;
              renderRightH2H(ext, match);
              renderRightAnalytics(ext, liveStats, match);
              renderRightBracket(ext.bracket, match);
            })
            .catch(() => {});
        } catch (err) {
          console.error('Match detail fetch failed:', err);
        } finally {
          if (myToken === activeFetchToken) loader.classList.add('hidden');
        }
      }
    }

    function extractAllStatItems(stats) {
      let items = [];
      const seen = new Set();
      if (Array.isArray(stats) && stats.length > 0) {
        const periodAll = stats.find(s => String(s.period || '').toUpperCase() === 'ALL') || stats[0];
        // Format A: grouped stats (Football, Basketball)
        (periodAll.groups || []).forEach(g => {
          (g.statisticsItems || g.items || []).forEach(it => {
            const key = String(it.fields || it.name || '').toLowerCase();
            if (key && !seen.has(key)) {
              seen.add(key);
              items.push(it);
            }
          });
        });
        // Format B: flat statisticsItems directly under period (AM Football, Baseball, Table Tennis, Tennis, Hockey, Badminton)
        (periodAll.statisticsItems || periodAll.items || []).forEach(it => {
          const key = String(it.fields || it.name || '').toLowerCase();
          if (key && !seen.has(key)) {
            seen.add(key);
            items.push(it);
          }
        });
      }
      return items;
    }

    function renderRightDetails(events, stats, scores, match, cricketInnings = []) {
      const box = document.getElementById('rpTab-details');
      let html = '';

      // 1. Key Live Stats Quick Box (Possession, xG, Shots on Target, Dangerous Attacks — matching Screenshot 1!)
      const statItems = extractAllStatItems(stats);
      const findStat = (keys) => statItems.find(it => keys.includes(String(it.fields || it.name || '').toLowerCase()));
      const poss = findStat(['ball_possession', 'possession']);
      const xg = findStat(['xg_expected_goals', 'expected_goals']);
      const sot = findStat(['shots_on_target']);
      const dang = findStat(['dangerous_attacks']);
      const corners = findStat(['corner_kicks']);

      if (poss || xg || sot || dang || corners) {
        html += `<div class="bg-[#0A1B30] border border-[#22406A] rounded-xl p-3 space-y-2.5">`;
        if (poss) {
          const hp = parseFloat(poss.homeValue ?? poss.home) || 50;
          const ap = parseFloat(poss.awayValue ?? poss.away) || 50;
          html += `
            <div>
              <div class="text-center text-[10px] text-mist mb-1">Tỷ Lệ Kiểm Soát Bóng</div>
              <div class="flex h-5 rounded-full overflow-hidden text-[11px] font-bold">
                <div class="bg-blue-600 text-frost flex items-center justify-center" style="width:${hp}%">${poss.home ?? hp + '%'}</div>
                <div class="bg-[#FFB020] text-frost flex items-center justify-center" style="width:${ap}%">${poss.away ?? ap + '%'}</div>
              </div>
            </div>
          `;
        }
        const quickRows = [
          { item: sot, label: 'Cú sút trúng mục tiêu' },
          { item: dang, label: 'Tình huống tấn công nguy hiểm' },
          { item: xg, label: 'Bàn thắng kỳ vọng (xG)' },
          { item: corners, label: 'Phạt góc' }
        ].filter(r => r.item);

        quickRows.forEach(r => {
          html += `
            <div class="flex items-center justify-between text-[11px]">
              <span class="px-2 py-0.5 rounded-full bg-blue-950 text-blue-300 font-bold min-w-[34px] text-center">${r.item.home ?? r.item.homeValue}</span>
              <span class="text-frost/90">${r.label}</span>
              <span class="px-2 py-0.5 rounded-full bg-amber-950 text-amber-300 font-bold min-w-[34px] text-center">${r.item.away ?? r.item.awayValue}</span>
            </div>
          `;
        });
        html += `</div>`;
      }

      // 2. Period Breakdown (Basketball Lead Chart + Quarter Table Q1-Q4-OT + Overall Stats Card vs Football H1/H2)
      if (match.sport_key === 'basketball') {
        // 2a. Dynamic SVG Lead/Momentum Chart built from real Play-by-Play incidents!
        const scoreEvents = (events || []).filter(e => e.homeScore !== undefined && e.awayScore !== undefined).reverse();
        if (scoreEvents.length >= 2) {
          const diffs = [0, ...scoreEvents.map(e => (Number(e.homeScore) - Number(e.awayScore)))];
          const maxAbs = Math.max(5, ...diffs.map(d => Math.abs(d)));
          const W = 320, H = 90, midY = H / 2;
          const stepX = W / Math.max(1, diffs.length - 1);
          let pathPts = diffs.map((d, i) => {
            const x = (i * stepX).toFixed(1);
            const y = (midY - (d / maxAbs) * (midY - 8)).toFixed(1);
            return `${i === 0 ? 'M' : 'L'}${x},${y}`;
          }).join(' ');
          const lastDiff = diffs[diffs.length - 1];
          const strokeColor = lastDiff >= 0 ? '#8EA3BF' : '#FFB020';

          html += `
            <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl p-3">
              <div class="text-[10px] text-mist font-semibold mb-2">Thông số dẫn điểm (Lead Chart)</div>
              <div class="flex items-center gap-2">
                <div class="flex flex-col justify-between h-[80px] shrink-0">
                  <img src="${match.home_logo || ''}" class="w-4 h-4 object-contain" onerror="this.style.display='none'">
                  <img src="${match.away_logo || ''}" class="w-4 h-4 object-contain" onerror="this.style.display='none'">
                </div>
                <div class="flex-1 relative">
                  <svg viewBox="0 0 ${W} ${H}" class="w-full h-[84px] overflow-visible">
                    <line x1="0" y1="${midY}" x2="${W}" y2="${midY}" stroke="#22406A" stroke-dasharray="3,3" stroke-width="1"/>
                    <line x1="${W*0.25}" y1="0" x2="${W*0.25}" y2="${H}" stroke="#163152" stroke-width="1"/>
                    <line x1="${W*0.5}" y1="0" x2="${W*0.5}" y2="${H}" stroke="#163152" stroke-width="1"/>
                    <line x1="${W*0.75}" y1="0" x2="${W*0.75}" y2="${H}" stroke="#163152" stroke-width="1"/>
                    <path d="${pathPts}" fill="none" stroke="${strokeColor}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                  </svg>
                  <div class="flex justify-between text-[9px] text-mist mt-1 px-0.5">
                    <span>0</span><span>Q1</span><span>Q2</span><span>Q3</span><span>Q4</span>
                  </div>
                </div>
                <div class="flex flex-col justify-between h-[80px] text-[9px] text-mist font-score text-right w-5 shrink-0">
                  <span>+${maxAbs}</span><span>0</span><span>${maxAbs}</span>
                </div>
              </div>
            </div>
          `;
        }

        // 2b. Quarter Score Breakdown Table
        if (scores && scores.period_breakdown && scores.period_breakdown.home) {
          const hb = scores.period_breakdown.home;
          const ab = scores.period_breakdown.away || {};
          const hTot = hb.display ?? match.home_score ?? '-';
          const aTot = ab.display ?? match.away_score ?? '-';
          const hasOt = (hb.overtime > 0 || ab.overtime > 0 || hb.overTime > 0 || ab.overTime > 0);
          const hOt = hb.overtime ?? hb.overTime ?? '-';
          const aOt = ab.overtime ?? ab.overTime ?? '-';
          const cellCls = (v1, v2) => (Number(v1) > Number(v2)) ? 'text-[#FFB020] font-black' : 'text-frost';

          html += `
            <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl overflow-hidden">
              <table class="w-full text-center text-[11px]">
                <thead class="bg-[#163152] text-mist border-b border-[#22406A]">
                  <tr>
                    <th class="py-1.5 px-2 text-left">Đội</th>
                    <th class="py-1.5 px-1.5 text-frost">Tổng trận</th>
                    <th class="py-1.5 px-1.5">1</th>
                    <th class="py-1.5 px-1.5">2</th>
                    <th class="py-1.5 px-1.5">3</th>
                    <th class="py-1.5 px-1.5">4</th>
                    ${hasOt ? '<th class="py-1.5 px-1.5 text-[#FFB020]">OT</th>' : ''}
                  </tr>
                </thead>
                <tbody class="divide-y divide-[#22406A] font-score">
                  <tr>
                    <td class="py-2 px-2 text-left">
                      <div class="flex items-center gap-1.5">
                        <img src="${match.home_logo || ''}" class="w-4 h-4 object-contain" onerror="this.style.display='none'">
                        <span class="font-sans font-bold text-frost truncate max-w-[90px]">${match.home_team}</span>
                      </div>
                    </td>
                    <td class="py-2 px-1.5 ${cellCls(hTot, aTot)}">${hTot}</td>
                    <td class="py-2 px-1.5 ${cellCls(hb.period1, ab.period1)}">${hb.period1 ?? '-'}</td>
                    <td class="py-2 px-1.5 ${cellCls(hb.period2, ab.period2)}">${hb.period2 ?? '-'}</td>
                    <td class="py-2 px-1.5 ${cellCls(hb.period3, ab.period3)}">${hb.period3 ?? '-'}</td>
                    <td class="py-2 px-1.5 ${cellCls(hb.period4, ab.period4)}">${hb.period4 ?? '-'}</td>
                    ${hasOt ? `<td class="py-2 px-1.5 ${cellCls(hOt, aOt)}">${hOt}</td>` : ''}
                  </tr>
                  <tr>
                    <td class="py-2 px-2 text-left">
                      <div class="flex items-center gap-1.5">
                        <img src="${match.away_logo || ''}" class="w-4 h-4 object-contain" onerror="this.style.display='none'">
                        <span class="font-sans font-bold text-frost truncate max-w-[90px]">${match.away_team}</span>
                      </div>
                    </td>
                    <td class="py-2 px-1.5 ${cellCls(aTot, hTot)}">${aTot}</td>
                    <td class="py-2 px-1.5 ${cellCls(ab.period1, hb.period1)}">${ab.period1 ?? '-'}</td>
                    <td class="py-2 px-1.5 ${cellCls(ab.period2, hb.period2)}">${ab.period2 ?? '-'}</td>
                    <td class="py-2 px-1.5 ${cellCls(ab.period3, hb.period3)}">${ab.period3 ?? '-'}</td>
                    <td class="py-2 px-1.5 ${cellCls(ab.period4, hb.period4)}">${ab.period4 ?? '-'}</td>
                    ${hasOt ? `<td class="py-2 px-1.5 ${cellCls(aOt, hOt)}">${aOt}</td>` : ''}
                  </tr>
                </tbody>
              </table>
            </div>
          `;
        }

        // 2c. Overall Stats 4-row card (matching user's screenshot: Điểm, Bắt bóng bật bảng, Mất bóng, Tỷ lệ ném phạt %)
        const rebStat = findStat(['rebounds']);
        const tovStat = findStat(['turnovers']);
        const ftStat = findStat(['free_throws', 'free_throws_percent']);
        const bkbSummary = [
          { label: 'Điểm', h: match.home_score ?? '-', a: match.away_score ?? '-', hv: Number(match.home_score || 0), av: Number(match.away_score || 0) },
          rebStat ? { label: 'Bắt bóng bật bảng', h: rebStat.home ?? rebStat.homeValue, a: rebStat.away ?? rebStat.awayValue, hv: Number(rebStat.homeValue || 0), av: Number(rebStat.awayValue || 0) } : null,
          tovStat ? { label: 'Mất bóng', h: tovStat.home ?? tovStat.homeValue, a: tovStat.away ?? tovStat.awayValue, hv: Number(tovStat.homeValue || 0), av: Number(tovStat.awayValue || 0) } : null,
          ftStat ? { label: 'Tỷ lệ ném phạt (%)', h: (ftStat.homeValue ?? ftStat.home) + '%', a: (ftStat.awayValue ?? ftStat.away) + '%', hv: Number(ftStat.homeValue || 0), av: Number(ftStat.awayValue || 0) } : null,
        ].filter(Boolean);

        if (bkbSummary.length > 0) {
          html += `
            <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl p-3 space-y-2">
              <div class="text-[10px] text-mist font-semibold">Tổng quan thông số</div>
              ${bkbSummary.map(r => `
                <div class="flex items-center justify-between text-[11px] py-0.5">
                  <span class="px-2 py-0.5 rounded-full font-bold min-w-[34px] text-center ${r.hv > r.av ? 'bg-blue-600/80 text-frost' : 'text-frost/90'}">${r.h}</span>
                  <span class="text-frost/90">${r.label}</span>
                  <span class="px-2 py-0.5 rounded-full font-bold min-w-[34px] text-center ${r.av > r.hv ? 'border border-[#FFB020] text-[#FFB020]' : 'text-frost/90'}">${r.a}</span>
                </div>
              `).join('')}
            </div>
          `;
        }
      } else if (['tennis', 'badminton', 'volleyball', 'table_tennis'].includes(match.sport_key)) {
        // Set-based sports: Tennis (with Tiebreak superscripts x1..x5!), Badminton, Volleyball, Table Tennis (with Total Points!)
        const pb = (scores && scores.period_breakdown) ? scores.period_breakdown : {};
        const sets = [];
        for (let i = 1; i <= 7; i++) {
          if (pb[`p${i}`] && Array.isArray(pb[`p${i}`])) {
            sets.push({ idx: i, h: pb[`p${i}`][0], a: pb[`p${i}`][1], tb: pb[`x${i}`] || null });
          }
        }
        const hTot = (pb.ft && pb.ft[0] !== undefined) ? pb.ft[0] : (match.home_score ?? '-');
        const aTot = (pb.ft && pb.ft[1] !== undefined) ? pb.ft[1] : (match.away_score ?? '-');
        const totPts = scores?.total_points || null;
        const cellCls = (v1, v2) => (Number(v1) > Number(v2)) ? 'text-[#FFB020] font-black' : 'text-frost';

        html += `
          <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl overflow-hidden">
            <div class="bg-[#163152] px-3 py-1.5 border-b border-[#22406A] flex justify-between items-center text-[10px]">
              <span class="font-bold text-frost/90 uppercase">Bảng điểm từng Set</span>
              ${totPts ? `<span class="text-[#FFB020] font-score font-bold">Tổng điểm: ${totPts.home} - ${totPts.away}</span>` : ''}
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-center text-[11px]">
                <thead class="bg-[#10253F] text-mist border-b border-[#22406A]">
                  <tr>
                    <th class="py-1.5 px-2 text-left">VĐV / Đội</th>
                    <th class="py-1.5 px-1.5 text-frost">Set</th>
                    ${sets.map(s => `<th class="py-1.5 px-1.5">S${s.idx}</th>`).join('')}
                  </tr>
                </thead>
                <tbody class="divide-y divide-[#22406A] font-score">
                  <tr>
                    <td class="py-2 px-2 text-left font-sans font-bold text-frost truncate max-w-[110px]">${match.home_team}</td>
                    <td class="py-2 px-1.5 ${cellCls(hTot, aTot)}">${hTot}</td>
                    ${sets.map(s => `<td class="py-2 px-1.5 ${cellCls(s.h, s.a)}">${s.h}${s.tb && s.tb[0] !== undefined ? `<sup class="text-[8px] text-blue-400 ml-0.5">${s.tb[0]}</sup>` : ''}</td>`).join('')}
                  </tr>
                  <tr>
                    <td class="py-2 px-2 text-left font-sans font-bold text-frost truncate max-w-[110px]">${match.away_team}</td>
                    <td class="py-2 px-1.5 ${cellCls(aTot, hTot)}">${aTot}</td>
                    ${sets.map(s => `<td class="py-2 px-1.5 ${cellCls(s.a, s.h)}">${s.a}${s.tb && s.tb[1] !== undefined ? `<sup class="text-[8px] text-blue-400 ml-0.5">${s.tb[1]}</sup>` : ''}</td>`).join('')}
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        `;
      } else if (match.sport_key === 'baseball') {
        // Baseball: 9-Inning Line Score + R-H-E (Runs, Hits, Errors)
        const pb = (scores && scores.period_breakdown) ? scores.period_breakdown : {};
        const inns = [];
        for (let i = 1; i <= 10; i++) {
          if (pb[`p${i}`] && Array.isArray(pb[`p${i}`])) {
            inns.push({ idx: i, h: pb[`p${i}`][0], a: pb[`p${i}`][1] });
          }
        }
        const hR = (pb.ft && pb.ft[0] !== undefined) ? pb.ft[0] : (match.home_score ?? '-');
        const aR = (pb.ft && pb.ft[1] !== undefined) ? pb.ft[1] : (match.away_score ?? '-');
        const hH = pb.h ? pb.h[0] : '-', aH = pb.h ? pb.h[1] : '-';
        const hE = pb.e ? pb.e[0] : '-', aE = pb.e ? pb.e[1] : '-';

        html += `
          <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl overflow-hidden">
            <div class="bg-[#163152] px-3 py-1.5 border-b border-[#22406A] text-[10px] font-bold text-frost/90 uppercase">⚾ Bảng điểm Inning & R-H-E</div>
            <div class="overflow-x-auto">
              <table class="w-full text-center text-[10px] font-score">
                <thead class="bg-[#10253F] text-mist border-b border-[#22406A]">
                  <tr>
                    <th class="py-1.5 px-2 text-left font-sans">Đội</th>
                    ${inns.map(n => `<th class="py-1.5 px-1">${n.idx}</th>`).join('')}
                    <th class="py-1.5 px-1.5 text-[#FFB020] font-bold border-l border-[#22406A]">R</th>
                    <th class="py-1.5 px-1.5 text-blue-400">H</th>
                    <th class="py-1.5 px-1.5 text-red-400">E</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[#22406A]">
                  <tr>
                    <td class="py-2 px-2 text-left font-sans font-bold text-frost truncate max-w-[85px]">${match.home_team}</td>
                    ${inns.map(n => `<td class="py-2 px-1 text-frost/90">${n.h}</td>`).join('')}
                    <td class="py-2 px-1.5 font-black text-[#FFB020] border-l border-[#22406A]">${hR}</td>
                    <td class="py-2 px-1.5 text-blue-300">${hH}</td>
                    <td class="py-2 px-1.5 text-red-300">${hE}</td>
                  </tr>
                  <tr>
                    <td class="py-2 px-2 text-left font-sans font-bold text-frost truncate max-w-[85px]">${match.away_team}</td>
                    ${inns.map(n => `<td class="py-2 px-1 text-frost/90">${n.a}</td>`).join('')}
                    <td class="py-2 px-1.5 font-black text-[#FFB020] border-l border-[#22406A]">${aR}</td>
                    <td class="py-2 px-1.5 text-blue-300">${aH}</td>
                    <td class="py-2 px-1.5 text-red-300">${aE}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        `;
      } else if (match.sport_key === 'cricket') {
        // Cricket: Runs / Wickets (Overs) per Innings!
        const extra = scores?.extra_scores || {};
        const innings = extra.innings || [];
        html += `
          <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl p-3 space-y-2">
            <div class="text-[10px] font-bold text-[#FFB020] uppercase">🏏 Bảng Điểm Lượt Đánh (Innings Scorecard)</div>
            ${innings.length > 0 ? innings.map((inn, idx) => {
              const sideName = (inn[0] === 1) ? match.home_team : match.away_team;
              const runs = inn[1] ?? 0, overs = inn[2] ?? 0, wkts = inn[3] ?? 0;
              return `
                <div class="flex items-center justify-between bg-[#163152] border border-[#22406A] rounded-lg px-3 py-2 text-xs">
                  <div>
                    <div class="font-bold text-frost">${sideName}</div>
                    <div class="text-[10px] text-mist">Lượt đánh #${idx + 1}</div>
                  </div>
                  <div class="text-right font-score">
                    <div class="text-sm font-black text-[#FFB020]">${runs}/${wkts}</div>
                    <div class="text-[10px] text-mist">(${overs} Overs)</div>
                  </div>
                </div>
              `;
            }).join('') : `<div class="text-xs text-mist">Tổng điểm: <b>${match.home_score ?? '-'} - ${match.away_score ?? '-'}</b></div>`}
          </div>
        `;
      } else if (match.sport_key === 'american_football' || match.sport_key === 'ice_hockey') {
        // American Football (Q1-Q4-OT) & Ice Hockey (P1-P3-OT)
        const pb = (scores && scores.period_breakdown) ? scores.period_breakdown : {};
        const isAm = match.sport_key === 'american_football';
        const maxP = isAm ? 4 : 3;
        const pPrefix = isAm ? 'Q' : 'P';
        const periods = [];
        for (let i = 1; i <= maxP; i++) {
          if (pb[`p${i}`] && Array.isArray(pb[`p${i}`])) periods.push({ label: `${pPrefix}${i}`, h: pb[`p${i}`][0], a: pb[`p${i}`][1] });
        }
        if (pb.ot && Array.isArray(pb.ot)) periods.push({ label: 'OT', h: pb.ot[0], a: pb.ot[1] });
        const hTot = (pb.ft && pb.ft[0] !== undefined) ? pb.ft[0] : (match.home_score ?? '-');
        const aTot = (pb.ft && pb.ft[1] !== undefined) ? pb.ft[1] : (match.away_score ?? '-');
        const cellCls = (v1, v2) => (Number(v1) > Number(v2)) ? 'text-[#FFB020] font-black' : 'text-frost';

        html += `
          <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl overflow-hidden">
            <table class="w-full text-center text-[11px] font-score">
              <thead class="bg-[#163152] text-mist border-b border-[#22406A]">
                <tr>
                  <th class="py-1.5 px-2 text-left font-sans">Đội</th>
                  <th class="py-1.5 px-1.5 text-frost">Tổng</th>
                  ${periods.map(p => `<th class="py-1.5 px-1.5">${p.label}</th>`).join('')}
                </tr>
              </thead>
              <tbody class="divide-y divide-[#22406A]">
                <tr>
                  <td class="py-2 px-2 text-left font-sans font-bold text-frost truncate max-w-[100px]">${match.home_team}</td>
                  <td class="py-2 px-1.5 ${cellCls(hTot, aTot)}">${hTot}</td>
                  ${periods.map(p => `<td class="py-2 px-1.5 ${cellCls(p.h, p.a)}">${p.h}</td>`).join('')}
                </tr>
                <tr>
                  <td class="py-2 px-2 text-left font-sans font-bold text-frost truncate max-w-[100px]">${match.away_team}</td>
                  <td class="py-2 px-1.5 ${cellCls(aTot, hTot)}">${aTot}</td>
                  ${periods.map(p => `<td class="py-2 px-1.5 ${cellCls(p.a, p.h)}">${p.a}</td>`).join('')}
                </tr>
              </tbody>
            </table>
          </div>
        `;
      } else if (scores && scores.period_breakdown && scores.period_breakdown.home) {
        const hb = scores.period_breakdown.home;
        const ab = scores.period_breakdown.away || {};
        const p1h = hb.period1 ?? '-', p1a = ab.period1 ?? '-';
        const p2h = hb.period2 ?? '-', p2a = ab.period2 ?? '-';
        html += `
          <div class="bg-[#0A1B30] border border-[#22406A] rounded-lg p-2.5 flex justify-around text-center">
            <div><div class="text-[10px] text-mist">Hiệp 1</div><div class="font-bold text-frost">${p1h} - ${p1a}</div></div>
            <div><div class="text-[10px] text-mist">Hiệp 2</div><div class="font-bold text-frost">${p2h} - ${p2a}</div></div>
            <div><div class="text-[10px] text-mist">Trạng thái</div><div class="font-bold text-[#FFB020]">${match.state_category}</div></div>
          </div>
        `;
      }

      // 3. Real Chronological Timeline Events / Cricket Over-by-Over / AM Football Scoring Plays
      if (match.sport_key === 'cricket' && cricketInnings && cricketInnings.length > 0) {
        html += `<div class="text-[11px] font-bold text-[#FFB020] uppercase tracking-wide pt-1">🏏 Diễn biến từng Over (Ball-by-Ball)</div>`;
        cricketInnings.forEach(inn => {
          const overs = (inn.overs || []).slice(0, 8);
          html += `
            <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl p-2.5 space-y-2">
              <div class="text-[11px] font-bold text-frost border-b border-[#22406A] pb-1">Lượt ${inn.inning}: ${inn.team?.name || ''}</div>
              <div class="space-y-1.5">
                ${overs.map(ov => `
                  <div class="bg-[#163152] rounded-lg px-2.5 py-1.5 space-y-1">
                    <div class="flex justify-between text-[10px] text-mist">
                      <span><b class="text-frost">Over ${ov.over_number}</b> ${ov.batter?.name ? '• ' + ov.batter.name : ''}</span>
                      <span class="text-[#FFB020] font-bold">${ov.runs ?? ov.total_run ?? 0} runs${ov.wickets ? ` • ${ov.wickets}W` : ''}</span>
                    </div>
                    <div class="flex flex-wrap gap-1">
                      ${(ov.balls || []).map(b => {
                        const d = String(b.display ?? b.score ?? '0');
                        const bg = d === 'W' ? 'bg-red-600 text-frost' : (d === '6' ? 'bg-[#FFB020] text-black' : (d === '4' ? 'bg-blue-600 text-frost' : 'bg-[#0A1B30] text-frost/90 border border-[#22406A]'));
                        return `<span class="w-5 h-5 rounded-full inline-flex items-center justify-center text-[9px] font-bold ${bg}">${d}</span>`;
                      }).join('')}
                    </div>
                  </div>
                `).join('')}
              </div>
            </div>
          `;
        });
      } else if (match.sport_key === 'american_football' && events && events.length > 0) {
        const amPeriodMap = { 1: 'Hết Hiệp 1 (Q1)', 2: 'Hết Hiệp 2 (HT)', 3: 'Hết Hiệp 3 (Q3)', 4: 'Hết Hiệp 4 (Q4)', 100: 'Kết Thúc Trận Đấu (FT)' };
        const amPlayMap = { 1: '🏈 Touchdown (+6đ)', 2: '✅ Extra Point (+1đ)', 7: '🛡️ Safety (+2đ)', 8: '🥅 Field Goal (+3đ)', 9: '🔥 2PT Conversion (+2đ)' };
        html += `<div class="text-[11px] font-bold text-mist uppercase tracking-wide pt-1">Diễn biến ghi điểm (${events.length})</div>`;
        html += '<div class="space-y-1.5">' + events.map(e => {
          if (e.type === 1) {
            return `<div class="text-center text-[10px] font-bold text-[#FFB020] bg-[#163152] py-1 rounded">${amPeriodMap[e.extra] || 'Mốc hiệp đấu'}</div>`;
          }
          const mins = e.second ? Math.floor(e.second / 60) + "'" : '';
          const playLabel = amPlayMap[e.extra] || 'Ghi điểm';
          const isAway = (e.position === 2);
          const scoreTag = (e.homeScore !== undefined && e.awayScore !== undefined) ? `<span class="text-[10px] font-score font-bold text-frost bg-[#163152] px-1.5 py-0.5 rounded">${e.homeScore}-${e.awayScore}</span>` : '';
          return `
            <div class="flex items-center justify-between gap-2 bg-[#0A1B30] border border-[#22406A] rounded-lg px-2.5 py-1.5 ${isAway ? 'flex-row-reverse text-right' : ''}">
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-score text-[#FFB020] font-bold">${mins}</span>
                <span class="text-[11px] text-frost font-semibold">${playLabel}</span>
              </div>
              ${scoreTag}
            </div>
          `;
        }).join('') + '</div>';
      } else if (!events || events.length === 0) {
        if (match.sport_key === 'football') {
          html += `<div class="text-center py-6 text-mist">Chưa có diễn biến bàn thắng, thẻ phạt hay thay người.</div>`;
        }
      } else {
        html += `<div class="text-[11px] font-bold text-mist uppercase tracking-wide pt-1">Diễn biến trận đấu (${events.length})</div>`;
        html += '<div class="space-y-1.5">' + events.map(e => {
          const itype = String(e.incidentType || e.type || '').toLowerCase();
          const iclass = String(e.incidentClass || e.card_type || '').toLowerCase();
          const min = e.time ?? e.time_minute;
          const isHome = e.isHome;
          let icon = '•';
          let label = e.text || itype;

          if (itype === 'goal') {
            icon = (match.sport_key === 'basketball') ? '🏀' : '⚽';
            const pName = e.player?.name || e.scorer || 'Ghi điểm';
            const aName = e.assistPlayer?.name || e.assist1?.name || e.assist || '';
            const ptsBadge = (match.sport_key === 'basketball' && e.incidentScore) ? `<span class="text-[#FFB020] font-black">+${e.incidentScore}đ</span> ` : '';
            label = `${ptsBadge}<b class="text-frost">${pName}</b>` + (aName ? ` <span class="text-mist">(KT: ${aName})</span>` : '');
          } else if (itype === 'card' || iclass.includes('yellow') || iclass.includes('red')) {
            icon = iclass.includes('red') ? '🟥' : '🟨';
            label = `<span class="text-frost">${e.player?.name || e.scorer || 'Thẻ phạt'}</span>`;
          } else if (itype === 'substitution') {
            icon = '🔄';
            const pin = e.playerIn?.name || e.player_in || '';
            const pout = e.playerOut?.name || e.player_out || '';
            label = `<span class="text-emerald-400">▲ ${pin}</span> <span class="text-red-400">▼ ${pout}</span>`;
          } else if (itype === 'period') {
            return `<div class="text-center text-[10px] font-bold text-[#FFB020] bg-[#163152] py-1 rounded">${e.text || 'Hết hiệp'} ${e.homeScore !== undefined ? `(${e.homeScore} - ${e.awayScore})` : ''}</div>`;
          }

          const scoreTag = (e.homeScore !== undefined && e.awayScore !== undefined)
            ? `<span class="text-[10px] font-score font-bold text-frost bg-[#163152] px-1.5 py-0.5 rounded">${e.homeScore}-${e.awayScore}</span>`
            : '';

          return `
            <div class="flex items-center justify-between gap-2 bg-[#0A1B30] border border-[#22406A] rounded-lg px-2.5 py-1.5 ${isHome === false ? 'flex-row-reverse text-right' : ''}">
              <div class="flex items-center gap-2 min-w-0 ${isHome === false ? 'flex-row-reverse' : ''}">
                <span class="text-[11px] font-score font-bold text-[#FFB020] shrink-0">${min ? min + "'" : ''}</span>
                <span class="shrink-0">${icon}</span>
                <div class="text-[11px] truncate">${label}</div>
              </div>
              ${scoreTag}
            </div>
          `;
        }).join('') + '</div>';
      }

      box.innerHTML = html;
    }

    function renderRightStats(stats) {
      const box = document.getElementById('rpTab-stats');
      const items = extractAllStatItems(stats);

      if (items.length === 0) {
        box.innerHTML = `<div class="text-center py-8 text-mist">Chưa có dữ liệu thống kê chi tiết cho trận này.</div>`;
        return;
      }

      box.innerHTML = items.map(it => {
        const hv = parseFloat(it.homeValue ?? it.home_value ?? it.home) || 0;
        const av = parseFloat(it.awayValue ?? it.away_value ?? it.away) || 0;
        const tot = (hv + av) || 1;
        const hp = Math.round((hv / tot) * 100);
        const ap = 100 - hp;
        return `
          <div class="space-y-1">
            <div class="flex justify-between text-[11px]">
              <span class="font-bold text-blue-400">${it.home ?? hv}</span>
              <span class="text-frost/90">${formatStatName(it.fields || it.name)}</span>
              <span class="font-bold text-[#FFB020]">${it.away ?? av}</span>
            </div>
            <div class="flex h-1.5 rounded-full overflow-hidden bg-[#163152]">
              <div class="bg-blue-500" style="width:${hp}%"></div>
              <div class="bg-[#FFB020]" style="width:${ap}%"></div>
            </div>
          </div>
        `;
      }).join('');
    }

    function renderRightLineups(squad, meta, match) {
      const box = document.getElementById('rpTab-lineups');
      const tabBtn = document.getElementById('rpTabBtn-lineups');
      if (tabBtn) tabBtn.innerText = (match.sport_key === 'basketball') ? 'Bảng Điểm' : 'Đội Hình';

      const homeList = squad.home || [];
      const awayList = squad.away || [];

      if (homeList.length === 0 && awayList.length === 0) {
        box.innerHTML = `<div class="text-center py-8 text-mist">${match.sport_key === 'basketball' ? 'Chưa có bảng Box Score cầu thủ.' : 'Chưa có đội hình ra sân chính thức.'}</div>`;
        return;
      }

      // Basketball Box Score Table (PTS, MIN, REB, AST, FG, 3P, STL, BLK)
      if (match.sport_key === 'basketball') {
        const renderBoxScore = (arr, title) => {
          const sorted = [...arr].sort((a, b) => ((b.stats?.points ?? b.stats?.score ?? 0) - (a.stats?.points ?? a.stats?.score ?? 0)));
          return `
            <div class="bg-[#0A1B30] border border-[#22406A] rounded-lg overflow-hidden">
              <div class="bg-[#163152] px-3 py-2 border-b border-[#22406A] font-bold text-frost text-xs">${title}</div>
              <div class="overflow-x-auto">
                <table class="w-full text-[10px] text-center">
                  <thead class="text-mist border-b border-[#22406A] bg-[#10253F]">
                    <tr>
                      <th class="py-1.5 px-2 text-left">Cầu thủ</th>
                      <th class="py-1.5 px-1 text-[#FFB020]">PTS</th>
                      <th class="py-1.5 px-1">MIN</th>
                      <th class="py-1.5 px-1">REB</th>
                      <th class="py-1.5 px-1">AST</th>
                      <th class="py-1.5 px-1">FG</th>
                      <th class="py-1.5 px-1">3P</th>
                      <th class="py-1.5 px-1">STL</th>
                      <th class="py-1.5 px-1">BLK</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-[#163152] font-score">
                    ${sorted.map(p => {
                      const s = p.stats || {};
                      const pts = s.points ?? s.score ?? ((s.hits || 0) * 2 + (s.threePointsMades || 0) + (s.throwsMades || 0));
                      const min = s.timePresences ? s.timePresences + "'" : '-';
                      const reb = s.totalRebound ?? ((s.offensiveRebounds || 0) + (s.defensiveRebounds || 0));
                      const ast = s.assists ?? 0;
                      const fg = (s.hits !== undefined && s.shots !== undefined) ? `${s.hits}-${s.shots}` : '-';
                      const tp = (s.threePointsMades !== undefined && s.threePointsShots !== undefined) ? `${s.threePointsMades}-${s.threePointsShots}` : '-';
                      return `
                        <tr class="hover:bg-[#163152]">
                          <td class="py-1.5 px-2 text-left font-sans">
                            <div class="flex items-center gap-1.5 min-w-[100px]">
                              <span class="text-blue-400 font-score text-[9px] w-4">${p.shirt_number || ''}</span>
                              <span class="text-frost font-medium truncate max-w-[95px]">${p.short_name || p.name}</span>
                            </div>
                          </td>
                          <td class="py-1.5 px-1 font-black text-[#FFB020]">${pts}</td>
                          <td class="py-1.5 px-1 text-mist">${min}</td>
                          <td class="py-1.5 px-1 text-frost">${reb}</td>
                          <td class="py-1.5 px-1 text-frost">${ast}</td>
                          <td class="py-1.5 px-1 text-frost/90">${fg}</td>
                          <td class="py-1.5 px-1 text-frost/90">${tp}</td>
                          <td class="py-1.5 px-1 text-mist">${s.steals ?? 0}</td>
                          <td class="py-1.5 px-1 text-mist">${s.blocks ?? 0}</td>
                        </tr>
                      `;
                    }).join('')}
                  </tbody>
                </table>
              </div>
            </div>
          `;
        };
        box.innerHTML = renderBoxScore(homeList, match.home_team) + renderBoxScore(awayList, match.away_team);
        return;
      }

      const renderTeam = (arr, title, form) => {
        const starters = arr.filter(p => !p.is_substitute);
        const subs = arr.filter(p => p.is_substitute);
        const renderRow = p => `
          <div class="flex justify-between items-center text-[11px] py-1 border-b border-[#163152]/60 last:border-0">
            <div class="flex items-center gap-2 min-w-0">
              <span class="w-5 text-center font-score text-blue-400 font-bold shrink-0">${p.shirt_number || '-'}</span>
              ${p.avatar ? `<img src="${p.avatar}" class="w-5 h-5 rounded-full object-cover bg-[#163152] shrink-0" onerror="this.style.display='none'">` : ''}
              <span class="text-frost truncate">${p.name || ''}</span>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
              ${p.market_value ? `<span class="text-[9px] text-emerald-400">${p.market_value}</span>` : ''}
              <span class="text-[10px] text-mist font-score bg-[#163152] px-1 rounded">${p.position || ''}</span>
            </div>
          </div>
        `;

        return `
          <div class="bg-[#0A1B30] border border-[#22406A] rounded-lg p-2.5 space-y-2">
            <div class="flex justify-between items-center pb-1.5 border-b border-[#22406A]">
              <span class="font-bold text-frost">${title}</span>
              <span class="text-[10px] text-[#FFB020] font-score bg-[#163152] px-2 py-0.5 rounded">${form || ''}</span>
            </div>
            <div>
              <div class="text-[10px] font-bold text-mist uppercase mb-1">Đá chính (${starters.length})</div>
              ${starters.map(renderRow).join('')}
            </div>
            ${subs.length ? `
              <div class="pt-1">
                <div class="text-[10px] font-bold text-mist uppercase mb-1">Dự bị (${subs.length})</div>
                ${subs.map(renderRow).join('')}
              </div>
            ` : ''}
          </div>
        `;
      };

      box.innerHTML = renderTeam(homeList, match.home_team, meta.home_formation)
                    + renderTeam(awayList, match.away_team, meta.away_formation);
    }

    function renderRightStandings(rows, homeId, awayId) {
      const box = document.getElementById('rpTab-standings');
      if (!rows || rows.length === 0) {
        box.innerHTML = `<div class="text-center py-8 text-mist">Chưa có bảng xếp hạng cho giải đấu này.</div>`;
        return;
      }

      box.innerHTML = `
        <table class="w-full text-left text-[11px]">
          <thead class="text-mist border-b border-[#22406A]">
            <tr>
              <th class="py-1.5 px-1">#</th>
              <th class="py-1.5 px-1">Đội</th>
              <th class="py-1.5 px-1 text-center">Tr</th>
              <th class="py-1.5 px-1 text-center">HS</th>
              <th class="py-1.5 px-1 text-center font-bold text-frost">Đ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#22406A]">
            ${rows.map(r => {
              const highlight = (r.team_id === homeId || r.team_id === awayId) ? 'bg-[#163152] text-[#FFB020]' : '';
              return `
                <tr class="${highlight}">
                  <td class="py-1.5 px-1 font-bold">${r.position}</td>
                  <td class="py-1.5 px-1">
                    <div class="flex items-center gap-1.5">
                      <img src="${r.team_logo}" class="w-4 h-4 object-contain" onerror="this.style.display='none'">
                      <span class="truncate max-w-[150px] text-frost">${r.team_name}</span>
                    </div>
                  </td>
                  <td class="py-1.5 px-1 text-center text-frost/90">${r.matches}</td>
                  <td class="py-1.5 px-1 text-center text-frost/90">${r.diff > 0 ? '+' + r.diff : r.diff}</td>
                  <td class="py-1.5 px-1 text-center font-black text-[#FFB020]">${r.points}</td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      `;
    }

    // =========================================================================
    // TAB: TƯỜNG THUẬT (COMMENTARY — Football & Basketball)
    // =========================================================================
    function setCommentaryFilter(mode) {
      commentaryFilter = mode;
      renderRightCommentary(cachedLiveIncidents, currentRightMatch);
    }

    function renderRightCommentary(events, match) {
      const box = document.getElementById('rpTab-commentary');
      if (!box) return;
      const isBkb = match && match.sport_key === 'basketball';
      const list = (events || []).filter(e => {
        if (commentaryFilter === 'highlights') {
          const t = String(e.incidentType || e.type || '').toLowerCase();
          return ['goal', 'card', 'substitution', 'period'].includes(t) || (isBkb && Number(e.incidentScore || 0) >= 3);
        }
        return true;
      });

      let html = `
        <div class="flex items-center gap-2 mb-2">
          <button onclick="setCommentaryFilter('all')" class="px-3 py-1 rounded-full text-[11px] font-bold ${commentaryFilter === 'all' ? 'bg-[#FFB020] text-black' : 'bg-[#163152] text-frost/90'}">Tất cả (${(events || []).length})</button>
          <button onclick="setCommentaryFilter('highlights')" class="px-3 py-1 rounded-full text-[11px] font-bold ${commentaryFilter === 'highlights' ? 'bg-[#FFB020] text-black' : 'bg-[#163152] text-frost/90'}">Điểm nhấn</button>
        </div>
      `;

      if (list.length === 0) {
        html += `<div class="text-center py-8 text-mist">Chưa có diễn biến tường thuật trực tiếp cho trận đấu này.</div>`;
        box.innerHTML = html;
        return;
      }

      html += '<div class="space-y-2">' + list.slice(0, 60).map(e => {
        const itype = String(e.incidentType || e.type || '').toLowerCase();
        const iclass = String(e.incidentClass || '').toLowerCase();
        const min = e.time ?? e.time_minute;
        const clock = min ? `${min}'` : (e.timeSeconds !== undefined ? `${Math.floor(e.timeSeconds/60)}:${String(e.timeSeconds%60).padStart(2,'0')}` : '');
        const teamName = e.isHome === true ? match.home_team : (e.isHome === false ? match.away_team : '');
        const teamLogo = e.isHome === true ? match.home_logo : (e.isHome === false ? match.away_logo : '');

        let badge = '';
        let narrative = e.text || '';
        if (itype === 'goal') {
          if (isBkb) {
            const pts = e.incidentScore || 2;
            const pName = e.player?.name || 'Cầu thủ';
            const aName = e.assistPlayer?.name ? ` (Kiến tạo: ${e.assistPlayer.name})` : '';
            badge = `<span class="text-[#FFB020] font-bold uppercase text-[10px]">🏀 GHI +${pts} ĐIỂM</span>`;
            narrative = `<b>${pName}</b> (${teamName}) ghi ${pts} điểm${aName}. Tỷ số: <b>${e.homeScore} - ${e.awayScore}</b>.`;
          } else {
            const pName = e.player?.name || e.scorer || 'Cầu thủ';
            const aName = e.assist1?.name ? ` sau đường kiến tạo của <b>${e.assist1.name}</b>` : '';
            badge = `<span class="text-emerald-400 font-bold uppercase text-[10px]">⚽ BÀN THẮNG</span>`;
            narrative = `<b>${pName}</b> (${teamName}) ghi bàn${aName}! Tỷ số hiện tại: <b>${e.homeScore ?? ''} - ${e.awayScore ?? ''}</b>.`;
          }
        } else if (itype === 'card' || iclass.includes('yellow') || iclass.includes('red')) {
          const isRed = iclass.includes('red');
          badge = `<span class="${isRed ? 'text-red-400' : 'text-yellow-400'} font-bold uppercase text-[10px]">${isRed ? '🟥 THẺ ĐỎ' : '🟨 THẺ VÀNG'}</span>`;
          narrative = `Trọng tài rút ${isRed ? 'thẻ đỏ' : 'thẻ vàng'} cảnh cáo <b>${e.player?.name || 'cầu thủ'}</b> (${teamName}).`;
        } else if (itype === 'substitution') {
          badge = `<span class="text-blue-400 font-bold uppercase text-[10px]">🔄 THAY NGƯỜI</span>`;
          narrative = `<div><span class="text-emerald-400 font-semibold">▲ Vào sân:</span> ${e.playerIn?.name || ''}</div><div><span class="text-red-400 font-semibold">▼ Rời sân:</span> ${e.playerOut?.name || ''}</div>`;
        } else if (itype === 'period') {
          return `<div class="text-center text-[11px] font-bold text-emerald-400 bg-[#163152] border border-emerald-800/40 py-1.5 rounded-lg">${e.text || 'KẾT THÚC HIỆP'} ${e.homeScore !== undefined ? `(${e.homeScore} - ${e.awayScore})` : ''}</div>`;
        }

        return `
          <div class="bg-[#0A1B30] border border-[#22406A] rounded-lg p-2.5 space-y-1">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="font-score font-black text-[#FFB020] text-[11px]">${clock}</span>
                ${badge}
              </div>
              ${teamLogo ? `<img src="${teamLogo}" class="w-4 h-4 object-contain" onerror="this.style.display='none'">` : ''}
            </div>
            <div class="text-[11px] text-frost leading-relaxed">${narrative}</div>
          </div>
        `;
      }).join('') + '</div>';

      box.innerHTML = html;
    }

    // =========================================================================
    // TAB: ĐỐI ĐẦU (H2H — Football 3-color bar & Basketball 2-color bar + Filters)
    // =========================================================================
    function renderRightH2H(ext, match) {
      const box = document.getElementById('rpTab-h2h');
      if (!box) return;
      const allEvents = (ext && ext.h2h) ? ext.h2h : [];
      const duel = ext?.team_duel || null;
      const hId = ext?.home_id || '';
      const aId = ext?.away_id || '';
      const compId = ext?.comp_id || '';
      const isBkb = match && match.sport_key !== 'football';

      if (allEvents.length === 0 && duel) {
        const hw = Number(duel.homeWins || 0), aw = Number(duel.awayWins || 0), dr = Number(duel.draws || 0);
        const tot = (hw + aw + dr) || 1;
        const hp = Math.round((hw / tot) * 100);
        const dp = Math.round((dr / tot) * 100);
        const ap = Math.max(0, 100 - hp - dp);
        box.innerHTML = `
          <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl p-3.5 space-y-3">
            <div class="flex items-center justify-between text-xs">
              <img src="${match.home_logo || ''}" class="w-6 h-6 object-contain" onerror="this.style.display='none'">
              <span class="font-bold text-frost">Tổng đối đầu (${hw + aw + dr} trận)</span>
              <img src="${match.away_logo || ''}" class="w-6 h-6 object-contain" onerror="this.style.display='none'">
            </div>
            <div class="flex h-3 rounded-full overflow-hidden bg-[#163152]">
              <div class="bg-blue-500" style="width:${hp}%"></div>
              ${dr > 0 ? `<div class="bg-gray-500" style="width:${dp}%"></div>` : ''}
              <div class="bg-[#FFB020]" style="width:${ap}%"></div>
            </div>
            <div class="flex justify-between text-[11px] font-bold">
              <span class="text-blue-400">${match.home_team}: ${hw} Thắng (${hp}%)</span>
              ${dr > 0 ? `<span class="text-mist">${dr} Hòa</span>` : ''}
              <span class="text-[#FFB020]">${match.away_team}: ${aw} Thắng (${ap}%)</span>
            </div>
          </div>
        `;
        return;
      }

      if (allEvents.length === 0) {
        box.innerHTML = `<div class="text-center py-8 text-mist">Chưa có lịch sử đối đầu (H2H) giữa 2 đội này.</div>`;
        return;
      }

      const drawH2H = () => {
        const onlyHome = document.getElementById('h2hFilterHome')?.checked || false;
        const onlyComp = document.getElementById('h2hFilterComp')?.checked || false;

        const filtered = allEvents.filter(e => {
          if (e.home_score === null || e.away_score === null) return false;
          if (onlyHome && hId && e.home_id !== hId) return false;
          if (onlyComp && compId && e.tournament_id !== compId) return false;
          return true;
        });

        let homeWins = 0, draws = 0, awayWins = 0;
        filtered.forEach(e => {
          const hs = Number(e.home_score), as = Number(e.away_score);
          const homeIsCurrentHome = (e.home_id === hId);
          if (hs === as) draws++;
          else if ((hs > as && homeIsCurrentHome) || (as > hs && !homeIsCurrentHome)) homeWins++;
          else awayWins++;
        });

        const total = filtered.length || 1;
        const hp = Math.round((homeWins / total) * 100);
        const dp = isBkb ? 0 : Math.round((draws / total) * 100);
        const ap = Math.max(0, 100 - hp - dp);

        document.getElementById('h2hDynamicBody').innerHTML = `
          <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl p-3 space-y-2.5">
            <div class="flex items-center justify-between text-[11px]">
              <img src="${match.home_logo || ''}" class="w-5 h-5 object-contain" onerror="this.style.display='none'">
              <span class="font-bold text-frost">Tổng số trận (${filtered.length})</span>
              <img src="${match.away_logo || ''}" class="w-5 h-5 object-contain" onerror="this.style.display='none'">
            </div>
            <div class="flex h-2.5 rounded-full overflow-hidden bg-[#163152]">
              <div class="bg-blue-500" style="width:${hp}%"></div>
              ${!isBkb ? `<div class="bg-gray-500" style="width:${dp}%"></div>` : ''}
              <div class="bg-[#FFB020]" style="width:${ap}%"></div>
            </div>
            <div class="flex justify-between text-[10px] font-semibold">
              <div class="text-blue-400">${homeWins} Thắng (${hp}%)</div>
              ${!isBkb ? `<div class="text-mist">${draws} Hòa (${dp}%)</div>` : ''}
              <div class="text-[#FFB020]">${awayWins} Thắng (${ap}%)</div>
            </div>
          </div>

          <div class="space-y-1.5 mt-2">
            ${filtered.slice(0, 15).map(e => {
              const dt = e.timestamp ? new Date(e.timestamp * 1000).toLocaleDateString('vi-VN') : '';
              const hWin = Number(e.home_score) > Number(e.away_score);
              const aWin = Number(e.away_score) > Number(e.home_score);
              return `
                <div class="bg-[#0A1B30] border border-[#22406A] rounded-lg px-2.5 py-2 space-y-1">
                  <div class="flex justify-between text-[9px] text-mist">
                    <span class="truncate max-w-[200px]">${e.tournament_name}</span>
                    <span>${dt}</span>
                  </div>
                  <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 text-[11px]">
                    <div class="flex items-center justify-end gap-1.5 min-w-0">
                      <span class="truncate ${hWin ? 'text-frost font-bold' : 'text-mist'}">${e.home_name}</span>
                      <img src="${e.home_logo}" class="w-4 h-4 object-contain shrink-0" onerror="this.style.display='none'">
                    </div>
                    <div class="px-2 py-0.5 rounded bg-[#163152] font-score font-black text-center min-w-[46px]">
                      <span class="${hWin ? 'text-[#FFB020]' : 'text-frost/90'}">${e.home_score}</span> - <span class="${aWin ? 'text-[#FFB020]' : 'text-frost/90'}">${e.away_score}</span>
                    </div>
                    <div class="flex items-center justify-start gap-1.5 min-w-0">
                      <img src="${e.away_logo}" class="w-4 h-4 object-contain shrink-0" onerror="this.style.display='none'">
                      <span class="truncate ${aWin ? 'text-frost font-bold' : 'text-mist'}">${e.away_name}</span>
                    </div>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        `;
      };

      box.innerHTML = `
        <div class="flex items-center gap-4 text-[11px] text-frost/90 pb-1">
          <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="checkbox" id="h2hFilterHome" class="accent-[#FFB020]"> Sân nhà (${match.home_team})
          </label>
          <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="checkbox" id="h2hFilterComp" class="accent-[#FFB020]"> Cùng giải đấu
          </label>
        </div>
        <div id="h2hDynamicBody"></div>
      `;
      document.getElementById('h2hFilterHome').addEventListener('change', drawH2H);
      document.getElementById('h2hFilterComp').addEventListener('change', drawH2H);
      drawH2H();
    }

    // =========================================================================
    // TAB: PHÂN TÍCH DỮ LIỆU & AI DỰ ĐOÁN (DATALYTICS & AI PREDICTION)
    // =========================================================================
    function renderRightAnalytics(ext, stats, match) {
      const box = document.getElementById('rpTab-analytics');
      if (!box) return;
      const isBkb = match && match.sport_key === 'basketball';
      const an = ext?.analytics || null;
      const pr = ext?.prediction || null;
      let html = '';

      // 1. AI Prediction 1X2 + Corners + Cards (Football)
      if (pr && Array.isArray(pr.popular)) {
        const p1x2 = pr.popular.find(p => p.type === 'match_result_1x2')?.data;
        const pCor = pr.popular.find(p => p.type === 'corners')?.data;
        const pCard = pr.popular.find(p => p.type === 'cards')?.data;

        if (p1x2) {
          html += `
            <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl p-3 space-y-2.5">
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-[#FFB020]">🤖 AI Dự Đoán Kết Quả (1X2)</span>
                <span class="text-[10px] text-mist">Dự đoán tỷ số: <b class="text-frost">${p1x2.exact_home ?? '-'} - ${p1x2.exact_away ?? '-'}</b></span>
              </div>
              <div class="grid grid-cols-3 gap-2 text-center">
                <div class="p-2 rounded-lg border ${p1x2.highlighted === 'home' ? 'bg-blue-950/60 border-blue-500 text-frost' : 'bg-[#163152] border-[#22406A] text-frost/90'}">
                  <div class="text-[10px] truncate">${match.home_team}</div>
                  <div class="text-sm font-black mt-0.5">${p1x2.home}</div>
                </div>
                <div class="p-2 rounded-lg border ${p1x2.highlighted === 'draw' ? 'bg-gray-800 border-gray-400 text-frost' : 'bg-[#163152] border-[#22406A] text-frost/90'}">
                  <div class="text-[10px]">Hòa (X)</div>
                  <div class="text-sm font-black mt-0.5">${p1x2.draw}</div>
                </div>
                <div class="p-2 rounded-lg border ${p1x2.highlighted === 'away' ? 'bg-amber-950/60 border-[#FFB020] text-frost' : 'bg-[#163152] border-[#22406A] text-frost/90'}">
                  <div class="text-[10px] truncate">${match.away_team}</div>
                  <div class="text-sm font-black mt-0.5">${p1x2.away}</div>
                </div>
              </div>
              ${pCor || pCard ? `
                <div class="grid grid-cols-2 gap-2 pt-1 text-[10px]">
                  ${pCor ? `<div class="bg-[#163152] rounded p-2"><div class="text-mist">Phạt góc trung bình</div><div class="font-bold text-frost mt-0.5 font-score text-sm">${pCor.average} quả / trận</div></div>` : ''}
                  ${pCard ? `<div class="bg-[#163152] rounded p-2"><div class="text-mist">Thẻ phạt trung bình</div><div class="font-bold text-frost mt-0.5 font-score text-sm">${pCard.average} thẻ / trận</div></div>` : ''}
                </div>
              ` : ''}
            </div>
          `;
        }
      }

      // 2. Datalytics 6-Card Grid (Football)
      if (an) {
        html += `
          <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl p-3 space-y-2.5">
            <div class="flex items-center justify-between text-[11px]">
              <div class="flex items-center gap-1.5">
                <img src="${match.home_logo || ''}" class="w-5 h-5 object-contain" onerror="this.style.display='none'">
                <span class="px-1.5 py-0.5 rounded bg-blue-950 text-blue-300 font-bold">PPG ${an.home_ppg ?? '-'}</span>
              </div>
              <span class="font-bold text-mist">PHÂN TÍCH DỮ LIỆU</span>
              <div class="flex items-center gap-1.5">
                <span class="px-1.5 py-0.5 rounded bg-amber-950 text-[#FFB020] font-bold">PPG ${an.away_ppg ?? '-'}</span>
                <img src="${match.away_logo || ''}" class="w-5 h-5 object-contain" onerror="this.style.display='none'">
              </div>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center">
              <div class="bg-[#163152] border border-[#22406A] rounded-lg p-2">
                <div class="text-[10px] text-mist">Trên 2.5 bàn</div>
                <div class="text-sm font-black text-emerald-400">${an.o25_potential ?? '-'}%</div>
              </div>
              <div class="bg-[#163152] border border-[#22406A] rounded-lg p-2">
                <div class="text-[10px] text-mist">Trên 1.5 bàn</div>
                <div class="text-sm font-black text-blue-400">${an.o15_potential ?? '-'}%</div>
              </div>
              <div class="bg-[#163152] border border-[#22406A] rounded-lg p-2">
                <div class="text-[10px] text-mist">BTTS (2 đội ghi bàn)</div>
                <div class="text-sm font-black text-[#FFB020]">${an.btts_potential ?? '-'}%</div>
              </div>
              <div class="bg-[#163152] border border-[#22406A] rounded-lg p-2">
                <div class="text-[10px] text-mist">Bàn / Trận TB</div>
                <div class="text-sm font-black text-frost">${an.avg_potential ?? '-'}</div>
              </div>
              <div class="bg-[#163152] border border-[#22406A] rounded-lg p-2">
                <div class="text-[10px] text-mist">Phạt góc TB</div>
                <div class="text-sm font-black text-frost">${an.corners_potential ?? '-'}</div>
              </div>
              <div class="bg-[#163152] border border-[#22406A] rounded-lg p-2">
                <div class="text-[10px] text-mist">Thẻ phạt TB</div>
                <div class="text-sm font-black text-yellow-400">${an.cards_potential ?? '-'}</div>
              </div>
            </div>
          </div>
        `;
      }

      // 3. Basketball Data Analysis (computed from real H2H + stats)
      if (isBkb && ext && ext.h2h && ext.h2h.length > 0) {
        const finishedH2h = ext.h2h.filter(e => e.home_score !== null && e.away_score !== null);
        if (finishedH2h.length > 0) {
          let hPts = 0, aPts = 0, totPts = 0;
          finishedH2h.forEach(e => {
            const hs = Number(e.home_score), as = Number(e.away_score);
            totPts += (hs + as);
            if (e.home_id === ext.home_id) { hPts += hs; aPts += as; }
            else { hPts += as; aPts += hs; }
          });
          const n = finishedH2h.length;
          html += `
            <div class="bg-[#0A1B30] border border-[#22406A] rounded-xl p-3 space-y-2.5">
              <div class="text-[11px] font-bold text-[#FFB020]">🏀 Phân Tích Điểm Số Đối Đầu (${n} trận)</div>
              <div class="grid grid-cols-3 gap-2 text-center">
                <div class="bg-[#163152] border border-[#22406A] rounded-lg p-2">
                  <div class="text-[10px] text-mist truncate">${match.home_team} TB</div>
                  <div class="text-sm font-black text-blue-400">${(hPts / n).toFixed(1)}</div>
                </div>
                <div class="bg-[#163152] border border-[#22406A] rounded-lg p-2">
                  <div class="text-[10px] text-mist">Tổng điểm TB</div>
                  <div class="text-sm font-black text-emerald-400">${(totPts / n).toFixed(1)}</div>
                </div>
                <div class="bg-[#163152] border border-[#22406A] rounded-lg p-2">
                  <div class="text-[10px] text-mist truncate">${match.away_team} TB</div>
                  <div class="text-sm font-black text-[#FFB020]">${(aPts / n).toFixed(1)}</div>
                </div>
              </div>
            </div>
          `;
        }
      }

      if (!html) {
        html = `<div class="text-center py-8 text-mist">Chưa có dữ liệu phân tích AI chuyên sâu cho trận đấu này.</div>`;
      }
      box.innerHTML = html;
    }

    // =========================================================================
    // TAB: NHÁNH ĐẤU (BRACKET / CUP TREE)
    // =========================================================================
    function renderRightBracket(bracket, match) {
      const box = document.getElementById('rpTab-bracket');
      if (!box) return;
      const rounds = bracket?.rounds || (Array.isArray(bracket) ? bracket : []);
      if (!rounds || rounds.length === 0) {
        box.innerHTML = `<div class="text-center py-8 text-mist">Giải đấu hoặc vòng đấu này thi đấu theo thể thức tính điểm (chưa có Nhánh đấu loại trực tiếp).</div>`;
        return;
      }

      box.innerHTML = `
        <div class="flex gap-3 overflow-x-auto pb-2">
          ${rounds.map(r => `
            <div class="min-w-[200px] shrink-0 space-y-2">
              <div class="text-[10px] font-bold uppercase text-[#FFB020] bg-[#163152] px-2.5 py-1.5 rounded border border-[#22406A] text-center">
                ${r.description || r.name || 'Vòng loại trực tiếp'}
              </div>
              <div class="space-y-2">
                ${(r.blocks || r.matches || []).map(b => {
                  const p1 = b.participants?.[0] || {};
                  const p2 = b.participants?.[1] || {};
                  return `
                    <div class="bg-[#0A1B30] border border-[#22406A] rounded-lg p-2 space-y-1 text-[11px]">
                      <div class="flex justify-between items-center">
                        <span class="${p1.winner ? 'text-frost font-bold' : 'text-mist'} truncate">${p1.team?.name || 'TBD'}</span>
                        <span class="font-score font-bold ${p1.winner ? 'text-[#FFB020]' : 'text-mist'}">${b.homeTeamScore ?? '-'}</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="${p2.winner ? 'text-frost font-bold' : 'text-mist'} truncate">${p2.team?.name || 'TBD'}</span>
                        <span class="font-score font-bold ${p2.winner ? 'text-[#FFB020]' : 'text-mist'}">${b.awayTeamScore ?? '-'}</span>
                      </div>
                    </div>
                  `;
                }).join('')}
              </div>
            </div>
          `).join('')}
        </div>
      `;
    }

    // Initialize Flatpickr Date Range Picker
    flatpickr("#dateRangePicker", {
      mode: "range",
      dateFormat: "Y-m-d",
      defaultDate: ["{{ $dateFrom }}", "{{ $dateTo }}"],
      onClose: function(selectedDates) {
        if (selectedDates.length === 1) {
          const d = flatpickr.formatDate(selectedDates[0], "Y-m-d");
          document.getElementById('dateFromInput').value = d;
          document.getElementById('dateToInput').value = d;
          document.getElementById('dateRangeForm').submit();
        } else if (selectedDates.length === 2) {
          document.getElementById('dateFromInput').value = flatpickr.formatDate(selectedDates[0], "Y-m-d");
          document.getElementById('dateToInput').value = flatpickr.formatDate(selectedDates[1], "Y-m-d");
          document.getElementById('dateRangeForm').submit();
        }
      }
    });

    // Initialize Right Panel on page load
    if (currentRightMatch) {
      window.__initialLoad = true;
      selectMatchForRightPanel(currentRightMatch, document.getElementById('match-row-' + currentRightMatch.id));
      window.__initialLoad = false;
    }

    // -------------------------------------------------------------------------
    // 3. REAL-TIME LIVE CLOCK TICKER (1s) + LIVE SCORE AUTO-POLLING (15s)
    // -------------------------------------------------------------------------
    function computeLiveClock(statusCode, periodStartTs) {
      if (statusCode === 6) return 'HT';
      if (statusCode === 13) return 'PEN';
      if (statusCode === 14) return 'ET';
      if (!periodStartTs || periodStartTs <= 0) return null;
      const elapsed = Math.max(0, Math.floor(Date.now() / 1000) - periodStartTs);
      const mins = Math.floor(elapsed / 60);
      const secs = String(elapsed % 60).padStart(2, '0');
      const m = mins + 1;
      if (statusCode === 5) return m > 45 ? "45+'" : `${m}:${secs}'`;
      if (statusCode === 7) return (m + 45) > 90 ? "90+'" : `${m + 45}:${secs}'`;
      if (statusCode === 33) return (m + 90) > 105 ? "105+'" : `${m + 90}:${secs}'`;
      if (statusCode === 42) return (m + 105) > 120 ? "120+'" : `${m + 105}:${secs}'`;
      return null;
    }

    // 1-second smooth live clock tick (just like UniScore!)
    setInterval(() => {
      document.querySelectorAll('.match-row[data-is-live="1"]').forEach(row => {
        const stCode = parseInt(row.dataset.statusCode || '0', 10);
        const pStart = parseInt(row.dataset.periodStartTs || '0', 10);
        const clockStr = computeLiveClock(stCode, pStart);
        if (clockStr) {
          const cell = row.querySelector('.live-clock-cell');
          if (cell) cell.innerText = clockStr;
          if (currentRightMatch && row.dataset.externalId === String(currentRightMatch.external_id)) {
            const rpLabel = document.getElementById('rpStatusLabel');
            if (rpLabel) rpLabel.innerText = clockStr;
          }
        }
      });
    }, 1000);

    // 15-second live score, status & right-panel auto-refresh
    async function pollLiveUpdates() {
      try {
        const res = await fetch(`/api/live-updates/{{ $sportKey }}`);
        const data = await res.json();
        const liveMatches = data.matches || [];
        const liveMap = {};
        liveMatches.forEach(lm => { liveMap[lm.external_id] = lm; });

        const globalEl = document.getElementById('globalLiveCount');
        if (globalEl && typeof data.live_count === 'number') {
          globalEl.innerText = data.live_count;
        }

        document.querySelectorAll('.match-row').forEach(row => {
          const eid = row.dataset.externalId;
          const lm = liveMap[eid];
          if (!lm) return;

          row.dataset.isLive = '1';
          row.dataset.statusCode = lm.status_code || 0;
          row.dataset.periodStartTs = lm.period_start_ts || 0;

          const scoreCell = row.querySelector('.live-score-cell');
          const newScoreStr = (lm.home_score !== null && lm.away_score !== null) ? `${lm.home_score} - ${lm.away_score}` : null;
          if (scoreCell && newScoreStr && scoreCell.innerText.trim() !== newScoreStr) {
            scoreCell.innerText = newScoreStr;
            // Goal flash highlight!
            scoreCell.classList.add('bg-emerald-600', 'text-frost');
            setTimeout(() => scoreCell.classList.remove('bg-emerald-600', 'text-frost'), 3500);
          }

          if (lm.readtime) {
            const clockCell = row.querySelector('.live-clock-cell');
            if (clockCell) clockCell.innerText = lm.readtime;
          }
        });

        // If active Right Panel match is live, silently refresh its Chi Tiết (events, possession) & Thống Kê in real-time!
        if (currentRightMatch && liveMap[currentRightMatch.external_id]) {
          const lm = liveMap[currentRightMatch.external_id];
          if (lm.home_score !== null && lm.away_score !== null) {
            document.getElementById('rpScoreBox').innerText = `${lm.home_score} - ${lm.away_score}`;
          }
          await refreshActiveRightPanelSilent(currentRightMatch);
        }
      } catch (e) {
        // Silent retry on next tick
      }
    }

    async function refreshActiveRightPanelSilent(match) {
      if (!match || !match.external_id || String(match.external_id).startsWith('manual_')) return;
      const myToken = activeFetchToken;
      try {
        const teams = (match.payload && match.payload.entities && match.payload.entities.teams_or_players) || {};
        const hId = teams.home?.id || '';
        const aId = teams.away?.id || '';
        const url = `/api/match-detail/${match.sport_key}/${match.external_id}?home_id=${encodeURIComponent(hId)}&away_id=${encodeURIComponent(aId)}`;
        const res = await fetch(url);
        const liveData = await res.json();
        if (myToken !== activeFetchToken) return;

        if (liveData.event) {
          const ev = liveData.event;
          const hs = ev.homeScore?.display ?? ev.homeScore?.current;
          const as = ev.awayScore?.display ?? ev.awayScore?.current;
          if (hs !== undefined && as !== undefined) {
            document.getElementById('rpScoreBox').innerText = `${hs} - ${as}`;
          }
        }
        renderRightDetails(
          liveData.incidents || [],
          liveData.statistics || [],
          liveData.event ? { period_breakdown: { home: liveData.event.homeScore, away: liveData.event.awayScore } } : {},
          match
        );
        renderRightStats(liveData.statistics || []);
        if (liveData.standings && liveData.standings.length) {
          renderRightStandings(liveData.standings, hId, aId);
        }
        document.getElementById('rpJsonPre').innerText = JSON.stringify(liveData, null, 2);
      } catch (err) {
        // Ignore transient network errors
      }
    }
    setInterval(pollLiveUpdates, 15000);
  </script>
</body>
</html>
