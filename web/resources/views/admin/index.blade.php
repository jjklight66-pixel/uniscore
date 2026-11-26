<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản trị hệ thống — UniScore</title>
  @include('partials.fl-head')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body class="fl min-h-screen flex flex-col">

  <!-- TOP HEADER -->
  <header class="bg-stand border-b border-seat px-4 lg:px-6 py-3 flex flex-wrap justify-between items-center gap-3 sticky top-0 z-40">
    <div class="flex items-center gap-3">
      <a href="{{ route('admin.index') }}" class="flex items-center gap-2.5">
        <span class="font-score text-2xl font-extrabold tracking-tight leading-none select-none">
          <span class="text-frost">UNI</span><span class="text-live">SCORE</span>
        </span>
        <span class="text-xs font-semibold text-mist border border-seat bg-night px-2 py-0.5 rounded">Quản trị</span>
      </a>
      <span class="hidden md:inline text-xs text-mist">{{ auth()->user()->email ?? 'Admin' }}</span>
    </div>

    <div class="flex items-center gap-2 text-xs">
      <a href="http://localhost:8501" target="_blank" class="fl-btn">Mission Control</a>
      <a href="{{ route('home') }}" class="fl-btn">Trang chủ</a>
      <form action="{{ route('admin.logout') }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="fl-btn fl-btn--danger">Đăng xuất</button>
      </form>
    </div>
  </header>

  <div class="flex-1 flex flex-col lg:flex-row max-w-[1600px] w-full mx-auto">

    <!-- LEFT NAVY NAVIGATION RAIL -->
    <aside class="w-full lg:w-60 shrink-0 bg-stand border-b lg:border-b-0 lg:border-r border-seat p-4 flex flex-col gap-5 lg:sticky lg:top-[56px] lg:self-start lg:max-h-[calc(100vh-56px)] lg:overflow-y-auto">
      <nav aria-label="Điều hướng quản trị" class="flex lg:flex-col gap-1.5 overflow-x-auto lg:overflow-visible pb-1 lg:pb-0">
        <a href="{{ route('admin.index', ['view' => 'matches']) }}"
           class="flex items-center justify-between gap-2 px-3 py-2 rounded text-xs font-semibold whitespace-nowrap transition-colors {{ $adminTab === 'matches' ? 'bg-stand2 text-frost border-l-2 border-live' : 'text-mist hover:text-frost hover:bg-stand2/60' }}">
          <span>Trận đấu</span>
          @if($adminTab === 'matches')
            <span class="w-1.5 h-1.5 rounded-full bg-live shrink-0"></span>
          @endif
        </a>

        <a href="{{ route('admin.index', ['view' => 'tournaments']) }}"
           class="flex items-center justify-between gap-2 px-3 py-2 rounded text-xs font-semibold whitespace-nowrap transition-colors {{ $adminTab === 'tournaments' ? 'bg-stand2 text-frost border-l-2 border-live' : 'text-mist hover:text-frost hover:bg-stand2/60' }}">
          <span>Giải đấu ưu tiên</span>
          <span class="fl-num text-[11px] px-1.5 py-0.5 rounded bg-night border border-seat {{ $adminTab === 'tournaments' ? 'text-live' : 'text-mist' }}">{{ count($tournaments) }}</span>
        </a>

        <a href="#section-crawler"
           class="flex items-center justify-between gap-2 px-3 py-2 rounded text-xs font-semibold whitespace-nowrap text-mist hover:text-frost hover:bg-stand2/60 transition-colors">
          <span>Thu thập dữ liệu</span>
        </a>

        <a href="#section-system"
           class="flex items-center justify-between gap-2 px-3 py-2 rounded text-xs font-semibold whitespace-nowrap text-mist hover:text-frost hover:bg-stand2/60 transition-colors">
          <span>Bảo trì hệ thống</span>
        </a>
      </nav>

      <div class="hidden lg:block pt-4 border-t border-seat">
        <button type="button" onclick="openAddModal()" class="fl-btn fl-btn--primary w-full text-xs">
          + Thêm trận đấu
        </button>
      </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="flex-1 min-w-0 p-4 lg:p-6 space-y-6">

      <!-- Flash Notifications -->
      @if(session('success'))
        <div class="border border-goal/40 bg-goal/10 text-goal px-4 py-3 rounded-lg text-xs font-medium">
          {{ session('success') }}
        </div>
      @endif
      @if(session('error'))
        <div class="border border-alert/50 bg-alert/10 text-[#FF8A8D] px-4 py-3 rounded-lg text-xs font-medium">
          {{ session('error') }}
        </div>
      @endif

      <!-- 4 KPI CARDS -->
      <section aria-label="Thống kê hệ thống" class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="fl-panel p-4">
          <div class="text-xs text-mist mb-1">Tổng số trận lưu trữ</div>
          <div class="fl-display text-3xl text-frost">{{ $stats['total'] }}</div>
        </div>
        <div class="fl-panel p-4">
          <div class="text-xs text-mist mb-1 flex items-center gap-1.5">
            <span class="fl-live-dot"></span>
            <span>Trận đang Live</span>
          </div>
          <div class="fl-display text-3xl text-live">{{ $stats['live'] }}</div>
        </div>
        <div class="fl-panel p-4">
          <div class="text-xs text-mist mb-1">Trận đã kết thúc</div>
          <div class="fl-display text-3xl text-frost">{{ $stats['finished'] }}</div>
        </div>
        <div class="fl-panel p-4">
          <div class="text-xs text-mist mb-1">Số giải đấu trong DB</div>
          <div class="fl-display text-3xl text-goal">{{ $stats['tournaments'] }}</div>
        </div>
      </section>

      <!-- =================================================================== -->
      <!-- VIEW 1: MATCHES CRUD + SEARCH & FILTERS                             -->
      <!-- =================================================================== -->
      @if($adminTab === 'matches')
        <section class="fl-panel overflow-hidden">
          <div class="px-4 py-3 border-b border-seat flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-frost">Danh sách trận đấu</h2>
            <button type="button" onclick="openAddModal()" class="fl-btn fl-btn--primary text-xs lg:hidden">
              + Thêm trận đấu
            </button>
          </div>

          <!-- Search & Filter Bar -->
          <form action="{{ route('admin.index') }}" method="GET" class="p-4 border-b border-seat bg-stand2/50 grid grid-cols-1 md:grid-cols-5 gap-3 text-xs">
            <input type="hidden" name="view" value="matches">
            <input type="text" name="q" value="{{ $fQuery }}" placeholder="Tìm tên đội hoặc giải đấu..." class="fl-input w-full">

            <select name="sport" class="fl-input w-full">
              <option value="all" {{ $fSport === 'all' ? 'selected' : '' }}>Tất cả 10 môn</option>
              @foreach($sports as $k => $info)
                <option value="{{ $k }}" {{ $fSport === $k ? 'selected' : '' }}>{{ $info['icon'] }} {{ $info['name'] }}</option>
              @endforeach
            </select>

            <select name="status" class="fl-input w-full">
              <option value="all" {{ $fStatus === 'all' ? 'selected' : '' }}>Tất cả trạng thái</option>
              <option value="live" {{ $fStatus === 'live' ? 'selected' : '' }}>Đang Live</option>
              <option value="upcoming" {{ $fStatus === 'upcoming' ? 'selected' : '' }}>Sắp đấu</option>
              <option value="finished" {{ $fStatus === 'finished' ? 'selected' : '' }}>Đã kết thúc</option>
              <option value="special" {{ $fStatus === 'special' ? 'selected' : '' }}>Hoãn / Hủy / Bỏ cuộc</option>
            </select>

            <input type="date" name="date" value="{{ $fDate }}" class="fl-input w-full">

            <div class="flex gap-2">
              <button type="submit" class="fl-btn fl-btn--primary flex-1">Lọc</button>
              <a href="{{ route('admin.index') }}" class="fl-btn">Đặt lại</a>
            </div>
          </form>

          <!-- Matches Table -->
          <div class="overflow-x-auto">
            <table class="fl-table">
              <thead>
                <tr>
                  <th class="w-12 text-center">Ghim</th>
                  <th>Môn</th>
                  <th>Giải đấu</th>
                  <th>Đội nhà và đội khách</th>
                  <th class="text-center whitespace-nowrap">Tỷ số</th>
                  <th>Trạng thái</th>
                  <th>Ngày đấu</th>
                  <th class="text-right">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                @forelse($matches as $m)
                  <tr>
                    <td class="text-center">
                      <form action="{{ route('admin.matches.pin', $m->id) }}" method="POST">
                        @csrf
                        <button type="submit" title="Ghim ưu tiên" class="text-base leading-none {{ $m->is_pinned ? 'text-live' : 'text-mist/40 hover:text-frost' }}">★</button>
                      </form>
                    </td>
                    <td class="text-frost font-medium whitespace-nowrap">
                      {{ ($sports[$m->sport_key]['icon'] ?? '') . ' ' . ($sports[$m->sport_key]['name'] ?? $m->sport_key) }}
                    </td>
                    <td class="text-mist max-w-[180px] truncate">{{ $m->tournament_name }}</td>
                    <td class="font-semibold text-frost">
                      {{ $m->home_team }} <span class="text-mist font-normal">vs</span> {{ $m->away_team }}
                    </td>
                    <td class="text-center">
                      <span class="fl-score text-base {{ str_contains($m->state_category, 'Live') ? 'text-live' : 'text-frost' }}">
                        {{ $m->home_score !== null ? $m->home_score : '-' }} : {{ $m->away_score !== null ? $m->away_score : '-' }}
                      </span>
                    </td>
                    <td>
                      @if(str_contains($m->state_category, 'Live'))
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-live/15 text-live border border-live/40">
                          <span class="fl-live-dot"></span>
                          {{ $m->state_category }}
                        </span>
                      @elseif(str_contains($m->state_category, 'Finished'))
                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-goal/15 text-goal border border-goal/35">
                          {{ $m->state_category }}
                        </span>
                      @elseif(str_contains($m->state_category, 'Special'))
                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-alert/15 text-[#FF8A8D] border border-alert/40">
                          {{ $m->state_category }}
                        </span>
                      @else
                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-night text-mist border border-seat">
                          {{ $m->state_category }}
                        </span>
                      @endif
                    </td>
                    <td class="fl-num text-mist whitespace-nowrap">{{ $m->match_date }}</td>
                    <td class="text-right whitespace-nowrap space-x-1.5">
                      <button type="button" onclick='openEditModal(@json($m))' class="fl-btn text-xs py-1 px-2.5">
                        Sửa
                      </button>
                      <form action="{{ route('admin.matches.destroy', $m->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Xóa trận đấu này khỏi CSDL?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="fl-btn fl-btn--danger text-xs py-1 px-2.5">
                          Xóa
                        </button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="8" class="p-8 text-center text-mist">Không tìm thấy trận đấu nào khớp bộ lọc.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="p-4 border-t border-seat">
            {{ $matches->links() }}
          </div>
        </section>
      @endif

      <!-- =================================================================== -->
      <!-- VIEW 2: TOURNAMENT PRIORITY MANAGER                                 -->
      <!-- =================================================================== -->
      @if($adminTab === 'tournaments')
        <section class="fl-panel overflow-hidden">
          <div class="px-4 py-3 bg-stand2/50 border-b border-seat flex flex-wrap items-center justify-between gap-2">
            <div>
              <h2 class="text-sm font-semibold text-frost">Ưu tiên giải đấu</h2>
              <p class="text-xs text-mist mt-0.5">Ghim giải đấu để đẩy toàn bộ trận của giải đó lên đầu trang chủ.</p>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="fl-table">
              <thead>
                <tr>
                  <th>Môn</th>
                  <th>Tên giải đấu</th>
                  <th>Quốc gia / Khu vực</th>
                  <th class="text-center">Số trận</th>
                  <th class="text-center">Đang Live</th>
                  <th class="text-right">Ưu tiên hiển thị</th>
                </tr>
              </thead>
              <tbody>
                @foreach($tournaments as $t)
                  <tr>
                    <td class="text-frost font-medium whitespace-nowrap">{{ ($sports[$t->sport_key]['icon'] ?? '') . ' ' . ($sports[$t->sport_key]['name'] ?? $t->sport_key) }}</td>
                    <td class="font-semibold text-frost">{{ $t->tournament_name }}</td>
                    <td class="text-mist">{{ $t->tournament_country ?? 'Quốc tế' }}</td>
                    <td class="text-center fl-score text-base text-frost">{{ $t->match_count }}</td>
                    <td class="text-center fl-score text-base {{ $t->live_count > 0 ? 'text-live' : 'text-mist' }}">{{ $t->live_count }}</td>
                    <td class="text-right">
                      <form action="{{ route('admin.tournaments.pin') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="sport_key" value="{{ $t->sport_key }}">
                        <input type="hidden" name="tournament_name" value="{{ $t->tournament_name }}">
                        <button type="submit" class="fl-btn text-xs py-1 px-3 {{ $t->any_pinned ? 'fl-btn--primary' : '' }}">
                          {{ $t->any_pinned ? '★ Đang ghim (Bỏ ghim)' : '☆ Ghim cả giải' }}
                        </button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </section>
      @endif

      <!-- SCRAPER TRIGGER + SYSTEM MAINTENANCE BAR -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        <!-- Scraper On-Demand Stepped Form -->
        <section id="section-crawler" class="xl:col-span-2 fl-panel p-5 space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
              <h2 class="text-sm font-semibold text-frost">Thu thập dữ liệu UniScore</h2>
              <p class="text-xs text-mist">Cào dữ liệu theo môn, giải đấu và khoảng ngày với giới hạn tốc độ.</p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded bg-night text-goal border border-seat font-medium">Không giới hạn số trận</span>
          </div>

          <form action="{{ route('admin.scraper.trigger') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs items-stretch">
            @csrf
            <div class="bg-night/60 border border-seat rounded p-2.5 flex flex-col justify-between min-w-0">
              <label for="crawlSportSelect" class="text-mist block mb-1.5 font-medium whitespace-nowrap">1. Chọn môn</label>
              <select id="crawlSportSelect" name="sport" onchange="loadLeaguesForCrawl(this.value)" class="fl-input w-full h-9 text-xs">
                @foreach($sports as $k => $info)
                  <option value="{{ $k }}">{{ $info['icon'] }} {{ $info['name'] }}</option>
                @endforeach
              </select>
            </div>

            <div class="bg-night/60 border border-seat rounded p-2.5 flex flex-col justify-between min-w-0">
              <label for="crawlLeagueSelect" class="text-mist block mb-1.5 font-medium whitespace-nowrap">2. Chọn giải đấu</label>
              <select id="crawlLeagueSelect" name="league" class="fl-input w-full h-9 text-xs">
                <option value="">-- Tất cả giải đấu --</option>
              </select>
            </div>

            <div class="bg-night/60 border border-seat rounded p-2.5 flex flex-col justify-between min-w-0">
              <label for="crawlDateRange" class="text-mist block mb-1.5 font-medium whitespace-nowrap">3. Khoảng ngày</label>
              <input type="hidden" id="crawlDateFrom" name="date_from" value="{{ date('Y-m-d') }}">
              <input type="hidden" id="crawlDateTo" name="date_to" value="{{ date('Y-m-d') }}">
              <input type="text" id="crawlDateRange" readonly value="{{ date('Y-m-d') }}"
                     placeholder="Chọn khoảng ngày..."
                     class="fl-input w-full h-9 text-xs cursor-pointer">
            </div>

            <div class="bg-night/60 border border-seat rounded p-2.5 flex flex-col justify-between min-w-0">
              <label for="crawlDelaySelect" class="text-mist block mb-1.5 font-medium whitespace-nowrap">4. Giới hạn request</label>
              <select id="crawlDelaySelect" name="delay" class="fl-input w-full h-9 text-xs">
                <option value="1.0" selected>1.0s / req (Chuẩn an toàn)</option>
                <option value="0.5">0.5s / req (Nhanh)</option>
                <option value="1.5">1.5s / req (Rất an toàn)</option>
                <option value="2.0">2.0s / req (Chậm chống chặn)</option>
              </select>
            </div>

            <div class="flex items-end min-w-0">
              <button type="submit" class="fl-btn fl-btn--primary w-full h-[54px] lg:h-full">
                Bắt đầu cào
              </button>
            </div>
          </form>

          <!-- Live Background Crawl Progress & Console -->
          <div id="crawlMonitorBox" class="hidden bg-night border border-seat rounded-lg p-3.5 space-y-2.5 text-xs">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <span id="crawlStatusDot" class="fl-live-dot"></span>
                <span id="crawlStatusText" class="font-semibold text-live">Đang chạy cào...</span>
                <span id="crawlStatsPill" class="fl-num text-mist"></span>
              </div>
              <button type="button" id="btnStopCrawl" onclick="stopBackgroundCrawl()" class="hidden fl-btn fl-btn--danger text-xs py-1 px-2.5">
                Dừng cào
              </button>
            </div>
            <div class="h-1.5 w-full bg-stand rounded-full overflow-hidden border border-seat">
              <div id="crawlProgressBar" class="h-full bg-live transition-all duration-300" style="width: 0%"></div>
            </div>
            <div id="crawlLogsBox" class="max-h-28 overflow-y-auto font-mono text-[11px] text-mist space-y-0.5 bg-stand/60 p-2.5 rounded border border-seat"></div>
          </div>
        </section>

        <!-- System Maintenance Controls (1 col) -->
        <section id="section-system" class="fl-panel p-5 flex flex-col justify-between gap-4">
          <div>
            <h2 class="text-sm font-semibold text-frost">Bảo trì hệ thống</h2>
            <p class="text-xs text-mist mt-0.5">Đồng bộ cơ sở dữ liệu, làm mới bộ nhớ đệm và dọn trận cũ.</p>
          </div>
          <div class="grid grid-cols-1 gap-2.5 text-xs">
            <div class="grid grid-cols-2 gap-2.5">
              <form action="{{ route('admin.system.sync') }}" method="POST">
                @csrf
                <button type="submit" class="fl-btn w-full">
                  Đồng bộ DB
                </button>
              </form>
              <form action="{{ route('admin.system.clear_cache') }}" method="POST">
                @csrf
                <button type="submit" class="fl-btn w-full">
                  Xóa cache
                </button>
              </form>
            </div>
            <form action="{{ route('admin.system.prune_old') }}" method="POST" onsubmit="return confirm('Xóa toàn bộ trận đấu cũ hơn 7 ngày khỏi CSDL?');">
              @csrf
              <button type="submit" class="fl-btn fl-btn--danger w-full">
                Dọn trận cũ hơn 7 ngày
              </button>
            </form>
          </div>
        </section>
      </div>

    </main>
  </div>

  <!-- ADD MATCH MODAL -->
  <div id="addModal" class="fixed inset-0 bg-night/80 backdrop-blur-sm z-50 hidden justify-center items-center p-4">
    <div class="fl-panel max-w-lg w-full p-6 text-xs shadow-2xl">
      <div class="flex justify-between items-center pb-3 border-b border-seat mb-4">
        <h3 class="text-sm font-semibold text-frost">Thêm trận đấu thủ công</h3>
        <button type="button" onclick="closeAddModal()" class="text-mist hover:text-frost text-base px-1">✕</button>
      </div>
      <form action="{{ route('admin.matches.store') }}" method="POST" class="space-y-3">
        @csrf
        <input type="hidden" name="source" value="uniscore">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-mist block mb-1">Môn</label>
            <select name="sport_key" class="fl-input w-full">
              @foreach($sports as $k => $info)
                <option value="{{ $k }}">{{ $info['icon'] }} {{ $info['name'] }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="text-mist block mb-1">Ngày thi đấu</label>
            <input type="date" name="match_date" value="{{ date('Y-m-d') }}" required class="fl-input w-full">
          </div>
        </div>
        <div>
          <label class="text-mist block mb-1">Tên giải đấu</label>
          <input type="text" name="tournament_name" placeholder="Premier League" required class="fl-input w-full">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-mist block mb-1">Đội nhà</label>
            <input type="text" name="home_team" placeholder="Arsenal" required class="fl-input w-full">
          </div>
          <div>
            <label class="text-mist block mb-1">Đội khách</label>
            <input type="text" name="away_team" placeholder="Chelsea" required class="fl-input w-full">
          </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="text-mist block mb-1">Tỷ số chủ nhà</label>
            <input type="number" name="home_score" value="0" class="fl-input w-full">
          </div>
          <div>
            <label class="text-mist block mb-1">Tỷ số khách</label>
            <input type="number" name="away_score" value="0" class="fl-input w-full">
          </div>
          <div>
            <label class="text-mist block mb-1">Trạng thái</label>
            <select name="state_category" class="fl-input w-full">
              <option value="Live">Live</option>
              <option value="Upcoming" selected>Upcoming</option>
              <option value="Finished">Finished</option>
              <option value="Special (Postponed)">Hoãn</option>
            </select>
          </div>
        </div>
        <div class="pt-3 border-t border-seat flex justify-end gap-2">
          <button type="button" onclick="closeAddModal()" class="fl-btn">Hủy</button>
          <button type="submit" class="fl-btn fl-btn--primary">Lưu trận đấu</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT MATCH MODAL -->
  <div id="editModal" class="fixed inset-0 bg-night/80 backdrop-blur-sm z-50 hidden justify-center items-center p-4">
    <div class="fl-panel max-w-lg w-full p-6 text-xs shadow-2xl">
      <div class="flex justify-between items-center pb-3 border-b border-seat mb-4">
        <h3 class="text-sm font-semibold text-live">Chỉnh sửa trận đấu</h3>
        <button type="button" onclick="closeEditModal()" class="text-mist hover:text-frost text-base px-1">✕</button>
      </div>
      <form id="editMatchForm" method="POST" class="space-y-3">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-mist block mb-1">Môn</label>
            <select id="editSport" name="sport_key" class="fl-input w-full">
              @foreach($sports as $k => $info)
                <option value="{{ $k }}">{{ $info['icon'] }} {{ $info['name'] }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="text-mist block mb-1">Ngày đấu</label>
            <input type="date" id="editDate" name="match_date" required class="fl-input w-full">
          </div>
        </div>
        <div>
          <label class="text-mist block mb-1">Giải đấu</label>
          <input type="text" id="editTour" name="tournament_name" required class="fl-input w-full">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-mist block mb-1">Đội nhà</label>
            <input type="text" id="editHomeTeam" name="home_team" required class="fl-input w-full">
          </div>
          <div>
            <label class="text-mist block mb-1">Đội khách</label>
            <input type="text" id="editAwayTeam" name="away_team" required class="fl-input w-full">
          </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="text-mist block mb-1">Tỷ số chủ nhà</label>
            <input type="number" id="editHomeScore" name="home_score" class="fl-input w-full">
          </div>
          <div>
            <label class="text-mist block mb-1">Tỷ số khách</label>
            <input type="number" id="editAwayScore" name="away_score" class="fl-input w-full">
          </div>
          <div>
            <label class="text-mist block mb-1">Phút / Hiệp</label>
            <input type="text" id="editReadtime" name="readtime" placeholder="68' / Q3 / Set 2" class="fl-input w-full">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 items-center">
          <div>
            <label class="text-mist block mb-1">Trạng thái</label>
            <select id="editState" name="state_category" class="fl-input w-full">
              <option value="Live">Live (Đang diễn ra)</option>
              <option value="Upcoming">Upcoming (Chưa diễn ra)</option>
              <option value="Finished">Finished (Đã kết thúc)</option>
              <option value="Special (Postponed)">Special (Hoãn)</option>
              <option value="Special (Cancelled)">Special (Hủy bỏ)</option>
              <option value="Special (Retired)">Special (Bỏ cuộc)</option>
            </select>
          </div>
          <label class="flex items-center gap-2 pt-4 cursor-pointer text-frost">
            <input type="checkbox" id="editPinned" name="is_pinned" value="1" class="accent-live">
            <span>★ Ghim ưu tiên lên đầu trang</span>
          </label>
        </div>
        <div class="pt-3 border-t border-seat flex justify-end gap-2">
          <button type="button" onclick="closeEditModal()" class="fl-btn">Hủy</button>
          <button type="submit" class="fl-btn fl-btn--primary">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openAddModal() {
      const m = document.getElementById('addModal');
      m.classList.remove('hidden'); m.classList.add('flex');
    }
    function closeAddModal() {
      const m = document.getElementById('addModal');
      m.classList.add('hidden'); m.classList.remove('flex');
    }
    function openEditModal(match) {
      document.getElementById('editMatchForm').action = `/admin/matches/${match.id}`;
      document.getElementById('editSport').value = match.sport_key || 'football';
      document.getElementById('editDate').value = match.match_date || '';
      document.getElementById('editTour').value = match.tournament_name || '';
      document.getElementById('editHomeTeam').value = match.home_team || '';
      document.getElementById('editAwayTeam').value = match.away_team || '';
      document.getElementById('editHomeScore').value = match.home_score !== null ? match.home_score : '';
      document.getElementById('editAwayScore').value = match.away_score !== null ? match.away_score : '';
      document.getElementById('editReadtime').value = match.readtime || '';
      document.getElementById('editState').value = match.state_category || 'Upcoming';
      document.getElementById('editPinned').checked = !!match.is_pinned;

      const m = document.getElementById('editModal');
      m.classList.remove('hidden'); m.classList.add('flex');
    }
    function closeEditModal() {
      const m = document.getElementById('editModal');
      m.classList.add('hidden'); m.classList.remove('flex');
    }

    // 1. Cascading Sport -> Top Leagues Loader
    async function loadLeaguesForCrawl(sportKey) {
      const sel = document.getElementById('crawlLeagueSelect');
      if (!sel) return;
      sel.innerHTML = '<option value="">Đang tải danh sách giải...</option>';
      try {
        const res = await fetch(`/api/top-leagues/${sportKey}`);
        const data = await res.json();
        const leagues = data.leagues || [];
        sel.innerHTML = '<option value="">-- Tất cả giải đấu --</option>' +
          leagues.map(l => `<option value="${l.name}">${l.name}${l.country ? ' (' + l.country + ')' : ''}</option>`).join('');
      } catch (e) {
        sel.innerHTML = '<option value="">-- Tất cả giải đấu --</option>';
      }
    }

    // 2. Flatpickr Date Range Picker (Từ ngày A -> Đến ngày B)
    if (document.getElementById('crawlDateRange')) {
      flatpickr("#crawlDateRange", {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate: ["{{ date('Y-m-d') }}", "{{ date('Y-m-d') }}"],
        onClose: function(selectedDates) {
          if (selectedDates.length === 1) {
            const d = flatpickr.formatDate(selectedDates[0], "Y-m-d");
            document.getElementById('crawlDateFrom').value = d;
            document.getElementById('crawlDateTo').value = d;
          } else if (selectedDates.length === 2) {
            document.getElementById('crawlDateFrom').value = flatpickr.formatDate(selectedDates[0], "Y-m-d");
            document.getElementById('crawlDateTo').value = flatpickr.formatDate(selectedDates[1], "Y-m-d");
          }
        }
      });
    }

    // 3. Live Background Crawl Monitor (2s Polling)
    async function pollCrawlStatus() {
      try {
        const res = await fetch('/admin/scraper/status');
        const st = await res.json();
        const box = document.getElementById('crawlMonitorBox');
        if (!st || (!st.running && (!st.logs || st.logs.length === 0))) return;

        box.classList.remove('hidden');
        const dot = document.getElementById('crawlStatusDot');
        const txt = document.getElementById('crawlStatusText');
        const pill = document.getElementById('crawlStatsPill');
        const stopBtn = document.getElementById('btnStopCrawl');
        const logsEl = document.getElementById('crawlLogsBox');
        const bar = document.getElementById('crawlProgressBar');

        const totalDays = Number(st.total_days) || 0;
        const daysDone = Number(st.days_done) || 0;
        const pct = totalDays > 0 ? Math.min(100, Math.round((daysDone / totalDays) * 100)) : (st.running ? 15 : 100);
        if (bar) {
          bar.style.width = `${st.running ? Math.max(8, pct) : 100}%`;
          bar.className = st.running ? 'h-full bg-live transition-all duration-300' : 'h-full bg-goal transition-all duration-300';
        }

        if (st.running) {
          dot.className = 'fl-live-dot';
          txt.innerText = `Đang cào [${st.sport}] ngày ${st.current_date} (${st.days_done}/${st.total_days} ngày)...`;
          txt.className = 'font-semibold text-live';
          stopBtn.classList.remove('hidden');
        } else {
          dot.className = 'w-2 h-2 rounded-full bg-goal inline-block';
          txt.innerText = `Hoàn tất cào [${st.sport}] (${st.date_from} - ${st.date_to})`;
          txt.className = 'font-semibold text-goal';
          stopBtn.classList.add('hidden');
        }

        pill.innerText = `Đã lưu: ${st.total_saved || 0} trận, ${st.total_requests || 0} req (${st.delay_sec || 1}s/req)`;
        logsEl.innerHTML = (st.logs || []).slice(-12).map(l => `<div>${l}</div>`).join('');
        logsEl.scrollTop = logsEl.scrollHeight;
      } catch (e) {}
    }

    async function stopBackgroundCrawl() {
      await fetch('/admin/scraper/stop', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
      });
    }

    loadLeaguesForCrawl(document.getElementById('crawlSportSelect')?.value || 'football');
    pollCrawlStatus();
    setInterval(pollCrawlStatus, 2000);
  </script>
</body>
</html>
