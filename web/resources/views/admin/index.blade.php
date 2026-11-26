<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Quản Trị Hệ Thống UNISCORE</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <style>
    :root { color-scheme: dark; }
    body { background-color: #0b0d12; color: #d1d5db; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    select, input { background-color: #0f1219 !important; color: #f3f4f6 !important; border-color: #2a3040 !important; color-scheme: dark; }
    select option { background-color: #141821; color: #f3f4f6; }
  </style>
</head>
<body class="min-h-screen flex flex-col">

  <!-- TOP HEADER -->
  <header class="bg-[#12151e] border-b border-[#232836] px-6 py-3 flex flex-wrap justify-between items-center gap-3 sticky top-0 z-50">
    <div class="flex items-center gap-3">
      <a href="{{ route('admin.index') }}" class="text-lg font-black text-white flex items-center gap-2">
        <span class="text-[#3b82f6]">UNI</span><span class="text-[#f59e0b]">SCORE</span>
        <span class="text-xs font-bold bg-amber-950/70 text-[#f59e0b] px-2 py-0.5 rounded border border-amber-700/60">ADMIN PANEL</span>
      </a>
      <span class="hidden md:inline text-xs text-gray-400">👤 {{ auth()->user()->email ?? 'Admin' }}</span>
    </div>

    <div class="flex items-center gap-2.5 text-xs font-semibold">
      <a href="http://localhost:8501" target="_blank" class="bg-purple-950/60 hover:bg-purple-900 text-purple-200 border border-purple-700/60 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition">
        <span>⚡</span> Mission Control (8501)
      </a>
      <a href="{{ route('home') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition">
        <span>🌐</span> Xem Trang Chủ
      </a>
      <form action="{{ route('admin.logout') }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="bg-red-950/60 hover:bg-red-900 text-red-300 border border-red-800/60 px-3 py-1.5 rounded-lg transition">
          🚪 Đăng xuất
        </button>
      </form>
    </div>
  </header>

  <div class="max-w-[1440px] w-full mx-auto p-5 space-y-5">

    <!-- Flash Notifications -->
    @if(session('success'))
      <div class="bg-emerald-950/70 border border-emerald-700 text-emerald-200 px-4 py-3 rounded-xl text-xs font-medium">
        ✅ {{ session('success') }}
      </div>
    @endif
    @if(session('error'))
      <div class="bg-red-950/70 border border-red-700 text-red-200 px-4 py-3 rounded-xl text-xs font-medium">
        ❌ {{ session('error') }}
      </div>
    @endif

    <!-- 4 KPI CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
      <div class="bg-[#141720] border border-[#232836] rounded-xl p-4">
        <div class="text-gray-400 mb-1">Tổng số trận lưu trữ</div>
        <div class="text-2xl font-black text-white">{{ $stats['total'] }}</div>
      </div>
      <div class="bg-[#141720] border border-[#232836] rounded-xl p-4">
        <div class="text-gray-400 mb-1">Trận đang Live</div>
        <div class="text-2xl font-black text-red-400">{{ $stats['live'] }}</div>
      </div>
      <div class="bg-[#141720] border border-[#232836] rounded-xl p-4">
        <div class="text-gray-400 mb-1">Trận đã kết thúc (Khóa DB)</div>
        <div class="text-2xl font-black text-blue-400">{{ $stats['finished'] }}</div>
      </div>
      <div class="bg-[#141720] border border-[#232836] rounded-xl p-4">
        <div class="text-gray-400 mb-1">Số giải đấu trong DB</div>
        <div class="text-2xl font-black text-emerald-400">{{ $stats['tournaments'] }}</div>
      </div>
    </div>

    <!-- SCRAPER TRIGGER + SYSTEM MAINTENANCE BAR -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Scraper On-Demand Trigger (Unlimited Matches + Cascading Sport -> League + Date Range + Rate Limit) -->
      <div class="lg:col-span-2 bg-[#141720] border border-[#232836] rounded-xl p-4 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-xs font-bold text-white uppercase tracking-wide flex items-center gap-2">
            <span>🚀</span> Khởi Chạy Cào Dữ Liệu UniScore (Không Giới Hạn Số Trận)
          </h3>
          <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-950/70 text-emerald-400 border border-emerald-800/60 font-semibold">∞ Unlimited • Anti-Block Rate Limit</span>
        </div>

        <form action="{{ route('admin.scraper.trigger') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 text-xs">
          @csrf
          <div>
            <label class="text-gray-400 block mb-1">1. Chọn Môn:</label>
            <select id="crawlSportSelect" name="sport" onchange="loadLeaguesForCrawl(this.value)" class="w-full border rounded-lg p-2">
              @foreach($sports as $k => $info)
                <option value="{{ $k }}">{{ $info['icon'] }} {{ $info['name'] }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="text-gray-400 block mb-1">2. Chọn Giải đấu:</label>
            <select id="crawlLeagueSelect" name="league" class="w-full border rounded-lg p-2">
              <option value="">-- Tất cả giải đấu --</option>
            </select>
          </div>

          <div>
            <label class="text-gray-400 block mb-1">3. Từ ngày A → Ngày B:</label>
            <input type="hidden" id="crawlDateFrom" name="date_from" value="{{ date('Y-m-d') }}">
            <input type="hidden" id="crawlDateTo" name="date_to" value="{{ date('Y-m-d') }}">
            <input type="text" id="crawlDateRange" readonly value="{{ date('Y-m-d') }}"
                   placeholder="Chọn khoảng ngày..."
                   class="w-full border rounded-lg p-2 cursor-pointer">
          </div>

          <div>
            <label class="text-gray-400 block mb-1">4. Giới hạn Request:</label>
            <select name="delay" class="w-full border rounded-lg p-2">
              <option value="1.0" selected>1.0s / req (Chuẩn an toàn)</option>
              <option value="0.5">0.5s / req (Nhanh)</option>
              <option value="1.5">1.5s / req (Rất an toàn)</option>
              <option value="2.0">2.0s / req (Chậm chống chặn)</option>
            </select>
          </div>

          <div class="flex items-end">
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold p-2 rounded-lg transition shadow">
              ▶ Bắt Đầu Cào
            </button>
          </div>
        </form>

        <!-- Live Background Crawl Progress & Console -->
        <div id="crawlMonitorBox" class="hidden bg-[#0a0c10] border border-[#232836] rounded-lg p-3 space-y-2 text-[11px]">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span id="crawlStatusDot" class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
              <span id="crawlStatusText" class="font-bold text-emerald-400">Đang chạy cào...</span>
              <span id="crawlStatsPill" class="text-gray-400 font-mono"></span>
            </div>
            <button type="button" id="btnStopCrawl" onclick="stopBackgroundCrawl()" class="hidden px-2.5 py-0.5 rounded bg-red-600 hover:bg-red-700 text-white font-bold text-[10px]">
              ⏹ Dừng cào
            </button>
          </div>
          <div id="crawlLogsBox" class="max-h-28 overflow-y-auto font-mono text-[10px] text-gray-300 space-y-0.5 bg-black/50 p-2 rounded border border-[#1f2433]"></div>
        </div>
      </div>

      <!-- System Maintenance Controls (1 col) -->
      <div class="bg-[#141720] border border-[#232836] rounded-xl p-4 flex flex-col justify-between">
        <h3 class="text-xs font-bold text-white uppercase tracking-wide mb-3 flex items-center gap-2">
          <span>⚙️</span> Bảo Trì Hệ Thống & Cache
        </h3>
        <div class="grid grid-cols-1 gap-2 text-xs">
          <div class="grid grid-cols-2 gap-2">
            <form action="{{ route('admin.system.sync') }}" method="POST">
              @csrf
              <button type="submit" class="w-full bg-blue-950/70 hover:bg-blue-900 text-blue-300 border border-blue-800/70 py-2 px-2.5 rounded-lg font-semibold transition">
                🔄 Đồng bộ DB
              </button>
            </form>
            <form action="{{ route('admin.system.clear_cache') }}" method="POST">
              @csrf
              <button type="submit" class="w-full bg-amber-950/70 hover:bg-amber-900 text-amber-300 border border-amber-800/70 py-2 px-2.5 rounded-lg font-semibold transition">
                🧹 Xóa Cache
              </button>
            </form>
          </div>
          <form action="{{ route('admin.system.prune_old') }}" method="POST" onsubmit="return confirm('Xóa toàn bộ trận đấu cũ hơn 7 ngày khỏi CSDL?');">
            @csrf
            <button type="submit" class="w-full bg-red-950/50 hover:bg-red-900/70 text-red-300 border border-red-800/60 py-1.5 px-2.5 rounded-lg font-semibold transition">
              🗑️ Dọn dẹp trận cũ (&gt; 7 ngày)
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- ADMIN VIEW SWITCHER: MATCHES CRUD vs TOURNAMENT PRIORITY -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-2 text-xs font-bold">
        <a href="{{ route('admin.index', ['view' => 'matches']) }}"
           class="px-4 py-2 rounded-lg border transition {{ $adminTab === 'matches' ? 'bg-[#f59e0b] text-black border-[#f59e0b]' : 'bg-[#141720] text-gray-300 border-[#232836] hover:text-white' }}">
          📋 Quản Lý Trận Đấu (CRUD)
        </a>
        <a href="{{ route('admin.index', ['view' => 'tournaments']) }}"
           class="px-4 py-2 rounded-lg border transition {{ $adminTab === 'tournaments' ? 'bg-[#f59e0b] text-black border-[#f59e0b]' : 'bg-[#141720] text-gray-300 border-[#232836] hover:text-white' }}">
          🏆 Quản Lý Ưu Tiên Giải Đấu ({{ count($tournaments) }})
        </a>
      </div>

      <button onclick="openAddModal()" class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-lg transition shadow">
        + Thêm Trận Đấu Thủ Công
      </button>
    </div>

    <!-- =================================================================== -->
    <!-- VIEW 1: MATCHES CRUD + SEARCH & FILTERS                             -->
    <!-- =================================================================== -->
    @if($adminTab === 'matches')
      <div class="bg-[#141720] border border-[#232836] rounded-xl overflow-hidden">
        <!-- Search & Filter Bar -->
        <form action="{{ route('admin.index') }}" method="GET" class="p-4 border-b border-[#232836] bg-[#191d28] grid grid-cols-1 md:grid-cols-5 gap-3 text-xs">
          <input type="hidden" name="view" value="matches">
          <input type="text" name="q" value="{{ $fQuery }}" placeholder="🔍 Tìm tên đội hoặc giải đấu..." class="border rounded-lg px-3 py-2">

          <select name="sport" class="border rounded-lg px-2.5 py-2">
            <option value="all" {{ $fSport === 'all' ? 'selected' : '' }}>Tất cả 10 môn</option>
            @foreach($sports as $k => $info)
              <option value="{{ $k }}" {{ $fSport === $k ? 'selected' : '' }}>{{ $info['icon'] }} {{ $info['name'] }}</option>
            @endforeach
          </select>

          <select name="status" class="border rounded-lg px-2.5 py-2">
            <option value="all" {{ $fStatus === 'all' ? 'selected' : '' }}>Tất cả trạng thái</option>
            <option value="live" {{ $fStatus === 'live' ? 'selected' : '' }}>🔴 Đang Live</option>
            <option value="upcoming" {{ $fStatus === 'upcoming' ? 'selected' : '' }}>⏳ Sắp đấu</option>
            <option value="finished" {{ $fStatus === 'finished' ? 'selected' : '' }}>✅ Đã kết thúc</option>
            <option value="special" {{ $fStatus === 'special' ? 'selected' : '' }}>⚠️ Hoãn / Hủy / Bỏ cuộc</option>
          </select>

          <input type="date" name="date" value="{{ $fDate }}" class="border rounded-lg px-2.5 py-2">

          <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-[#f59e0b] hover:bg-amber-600 text-black font-bold rounded-lg px-3 py-2">Lọc</button>
            <a href="{{ route('admin.index') }}" class="bg-[#0f1219] border border-[#2a3040] text-gray-300 hover:text-white rounded-lg px-3 py-2 flex items-center">Reset</a>
          </div>
        </form>

        <!-- Matches Table -->
        <div class="overflow-x-auto text-xs">
          <table class="w-full text-left">
            <thead class="bg-[#10131b] text-gray-400 border-b border-[#232836]">
              <tr>
                <th class="p-3 w-10 text-center">Ghim</th>
                <th class="p-3">Môn</th>
                <th class="p-3">Giải đấu</th>
                <th class="p-3">Đội nhà vs Đội khách</th>
                <th class="p-3 text-center">Tỷ số</th>
                <th class="p-3">Trạng thái</th>
                <th class="p-3">Ngày đấu</th>
                <th class="p-3 text-right">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#1f2433]">
              @forelse($matches as $m)
                <tr class="hover:bg-[#1c2130] transition">
                  <td class="p-3 text-center">
                    <form action="{{ route('admin.matches.pin', $m->id) }}" method="POST">
                      @csrf
                      <button type="submit" title="Ghim ưu tiên" class="text-base {{ $m->is_pinned ? 'text-[#f59e0b]' : 'text-gray-600 hover:text-gray-300' }}">★</button>
                    </form>
                  </td>
                  <td class="p-3 text-blue-400 font-semibold whitespace-nowrap">
                    {{ ($sports[$m->sport_key]['icon'] ?? '') . ' ' . ($sports[$m->sport_key]['name'] ?? $m->sport_key) }}
                  </td>
                  <td class="p-3 text-gray-200 max-w-[180px] truncate">{{ $m->tournament_name }}</td>
                  <td class="p-3 font-bold text-white">{{ $m->home_team }} <span class="text-gray-500 font-normal">vs</span> {{ $m->away_team }}</td>
                  <td class="p-3 text-center text-[#f59e0b] font-mono font-black">
                    {{ $m->home_score !== null ? $m->home_score : '-' }} : {{ $m->away_score !== null ? $m->away_score : '-' }}
                  </td>
                  <td class="p-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ str_contains($m->state_category, 'Live') ? 'bg-red-950 text-red-400 border border-red-800' : (str_contains($m->state_category, 'Finished') ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-gray-800 text-gray-300') }}">
                      {{ $m->state_category }}
                    </span>
                  </td>
                  <td class="p-3 font-mono text-gray-400">{{ $m->match_date }}</td>
                  <td class="p-3 text-right whitespace-nowrap space-x-2">
                    <button type="button" onclick='openEditModal(@json($m))'
                            class="bg-blue-950/70 hover:bg-blue-900 text-blue-300 border border-blue-800/70 px-2.5 py-1 rounded text-[11px] font-semibold transition">
                      ✏️ Sửa
                    </button>
                    <form action="{{ route('admin.matches.destroy', $m->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Xóa trận đấu này khỏi CSDL?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="bg-red-950/60 hover:bg-red-900 text-red-300 border border-red-800/60 px-2.5 py-1 rounded text-[11px] font-semibold transition">
                        🗑️ Xóa
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" class="p-8 text-center text-gray-500">Không tìm thấy trận đấu nào khớp bộ lọc.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="p-4 border-t border-[#232836]">
          {{ $matches->links() }}
        </div>
      </div>
    @endif

    <!-- =================================================================== -->
    <!-- VIEW 2: TOURNAMENT PRIORITY MANAGER                                 -->
    <!-- =================================================================== -->
    @if($adminTab === 'tournaments')
      <div class="bg-[#141720] border border-[#232836] rounded-xl overflow-hidden">
        <div class="p-4 bg-[#191d28] border-b border-[#232836] text-xs text-gray-300">
          Ghim ưu tiên toàn bộ giải đấu để đẩy tất cả trận của giải đó lên đầu trang chủ người dùng.
        </div>
        <div class="overflow-x-auto text-xs">
          <table class="w-full text-left">
            <thead class="bg-[#10131b] text-gray-400 border-b border-[#232836]">
              <tr>
                <th class="p-3">Môn</th>
                <th class="p-3">Tên Giải Đấu</th>
                <th class="p-3">Quốc Gia / Khu Vực</th>
                <th class="p-3 text-center">Số Trận Trong DB</th>
                <th class="p-3 text-center">Đang Live</th>
                <th class="p-3 text-right">Ưu Tiên Hiển Thị</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#1f2433]">
              @foreach($tournaments as $t)
                <tr class="hover:bg-[#1c2130]">
                  <td class="p-3 text-blue-400 font-semibold">{{ ($sports[$t->sport_key]['icon'] ?? '') . ' ' . ($sports[$t->sport_key]['name'] ?? $t->sport_key) }}</td>
                  <td class="p-3 font-bold text-white">{{ $t->tournament_name }}</td>
                  <td class="p-3 text-gray-400">{{ $t->tournament_country ?? 'Quốc tế' }}</td>
                  <td class="p-3 text-center font-mono text-white">{{ $t->match_count }}</td>
                  <td class="p-3 text-center font-mono {{ $t->live_count > 0 ? 'text-red-400 font-bold' : 'text-gray-500' }}">{{ $t->live_count }}</td>
                  <td class="p-3 text-right">
                    <form action="{{ route('admin.tournaments.pin') }}" method="POST" class="inline">
                      @csrf
                      <input type="hidden" name="sport_key" value="{{ $t->sport_key }}">
                      <input type="hidden" name="tournament_name" value="{{ $t->tournament_name }}">
                      <button type="submit" class="px-3 py-1 rounded-lg font-bold text-[11px] transition {{ $t->any_pinned ? 'bg-[#f59e0b] text-black' : 'bg-[#1b1f2e] text-gray-300 border border-[#2a3040] hover:border-[#f59e0b]' }}">
                        {{ $t->any_pinned ? '★ Đang Ghim (Bỏ ghim)' : '☆ Ghim cả giải lên đầu' }}
                      </button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif

  </div>

  <!-- ADD MATCH MODAL -->
  <div id="addModal" class="fixed inset-0 bg-black/80 z-50 hidden justify-center items-center p-4">
    <div class="bg-[#141720] border border-[#232836] rounded-2xl max-w-lg w-full p-6 text-xs">
      <div class="flex justify-between items-center pb-3 border-b border-[#232836] mb-4">
        <h3 class="text-sm font-bold text-white">+ Thêm Trận Đấu Mới Thủ Công</h3>
        <button onclick="closeAddModal()" class="text-gray-400 hover:text-white text-lg">✕</button>
      </div>
      <form action="{{ route('admin.matches.store') }}" method="POST" class="space-y-3">
        @csrf
        <input type="hidden" name="source" value="uniscore">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-gray-400 block mb-1">Môn:</label>
            <select name="sport_key" class="w-full border rounded-lg p-2">
              @foreach($sports as $k => $info)
                <option value="{{ $k }}">{{ $info['icon'] }} {{ $info['name'] }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="text-gray-400 block mb-1">Ngày thi đấu:</label>
            <input type="date" name="match_date" value="{{ date('Y-m-d') }}" required class="w-full border rounded-lg p-2">
          </div>
        </div>
        <div>
          <label class="text-gray-400 block mb-1">Tên Giải đấu:</label>
          <input type="text" name="tournament_name" placeholder="Premier League" required class="w-full border rounded-lg p-2">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-gray-400 block mb-1">Đội nhà:</label>
            <input type="text" name="home_team" placeholder="Arsenal" required class="w-full border rounded-lg p-2">
          </div>
          <div>
            <label class="text-gray-400 block mb-1">Đội khách:</label>
            <input type="text" name="away_team" placeholder="Chelsea" required class="w-full border rounded-lg p-2">
          </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="text-gray-400 block mb-1">Tỷ số Chủ nhà:</label>
            <input type="number" name="home_score" value="0" class="w-full border rounded-lg p-2">
          </div>
          <div>
            <label class="text-gray-400 block mb-1">Tỷ số Khách:</label>
            <input type="number" name="away_score" value="0" class="w-full border rounded-lg p-2">
          </div>
          <div>
            <label class="text-gray-400 block mb-1">Trạng thái:</label>
            <select name="state_category" class="w-full border rounded-lg p-2">
              <option value="Live">Live</option>
              <option value="Upcoming" selected>Upcoming</option>
              <option value="Finished">Finished</option>
              <option value="Special (Postponed)">Hoãn</option>
            </select>
          </div>
        </div>
        <div class="pt-3 border-t border-[#232836] flex justify-end gap-2">
          <button type="button" onclick="closeAddModal()" class="bg-[#1b1f2e] text-gray-300 px-4 py-2 rounded-lg">Hủy</button>
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-lg">Lưu Trận Đấu</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT MATCH MODAL -->
  <div id="editModal" class="fixed inset-0 bg-black/80 z-50 hidden justify-center items-center p-4">
    <div class="bg-[#141720] border border-[#232836] rounded-2xl max-w-lg w-full p-6 text-xs">
      <div class="flex justify-between items-center pb-3 border-b border-[#232836] mb-4">
        <h3 class="text-sm font-bold text-[#f59e0b]">✏️ Chỉnh Sửa Trận Đấu</h3>
        <button onclick="closeEditModal()" class="text-gray-400 hover:text-white text-lg">✕</button>
      </div>
      <form id="editMatchForm" method="POST" class="space-y-3">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-gray-400 block mb-1">Môn:</label>
            <select id="editSport" name="sport_key" class="w-full border rounded-lg p-2">
              @foreach($sports as $k => $info)
                <option value="{{ $k }}">{{ $info['icon'] }} {{ $info['name'] }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="text-gray-400 block mb-1">Ngày đấu:</label>
            <input type="date" id="editDate" name="match_date" required class="w-full border rounded-lg p-2">
          </div>
        </div>
        <div>
          <label class="text-gray-400 block mb-1">Giải đấu:</label>
          <input type="text" id="editTour" name="tournament_name" required class="w-full border rounded-lg p-2">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-gray-400 block mb-1">Đội nhà:</label>
            <input type="text" id="editHomeTeam" name="home_team" required class="w-full border rounded-lg p-2">
          </div>
          <div>
            <label class="text-gray-400 block mb-1">Đội khách:</label>
            <input type="text" id="editAwayTeam" name="away_team" required class="w-full border rounded-lg p-2">
          </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="text-gray-400 block mb-1">Tỷ số Chủ nhà:</label>
            <input type="number" id="editHomeScore" name="home_score" class="w-full border rounded-lg p-2">
          </div>
          <div>
            <label class="text-gray-400 block mb-1">Tỷ số Khách:</label>
            <input type="number" id="editAwayScore" name="away_score" class="w-full border rounded-lg p-2">
          </div>
          <div>
            <label class="text-gray-400 block mb-1">Phút / Hiệp:</label>
            <input type="text" id="editReadtime" name="readtime" placeholder="68' / Q3 / Set 2" class="w-full border rounded-lg p-2">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 items-center">
          <div>
            <label class="text-gray-400 block mb-1">Trạng thái:</label>
            <select id="editState" name="state_category" class="w-full border rounded-lg p-2">
              <option value="Live">Live (Đang diễn ra)</option>
              <option value="Upcoming">Upcoming (Chưa diễn ra)</option>
              <option value="Finished">Finished (Đã kết thúc)</option>
              <option value="Special (Postponed)">Special (Hoãn)</option>
              <option value="Special (Cancelled)">Special (Hủy bỏ)</option>
              <option value="Special (Retired)">Special (Bỏ cuộc)</option>
            </select>
          </div>
          <label class="flex items-center gap-2 pt-4 cursor-pointer text-gray-300">
            <input type="checkbox" id="editPinned" name="is_pinned" value="1" class="accent-[#f59e0b]">
            <span>★ Ghim ưu tiên lên đầu trang</span>
          </label>
        </div>
        <div class="pt-3 border-t border-[#232836] flex justify-end gap-2">
          <button type="button" onclick="closeEditModal()" class="bg-[#1b1f2e] text-gray-300 px-4 py-2 rounded-lg">Hủy</button>
          <button type="submit" class="bg-[#f59e0b] hover:bg-amber-600 text-black font-bold px-5 py-2 rounded-lg">💾 Lưu Thay Đổi</button>
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

        if (st.running) {
          dot.className = 'w-2 h-2 rounded-full bg-emerald-500 animate-ping';
          txt.innerText = `Đang cào [${st.sport}] ngày ${st.current_date} (${st.days_done}/${st.total_days} ngày)...`;
          txt.className = 'font-bold text-emerald-400';
          stopBtn.classList.remove('hidden');
        } else {
          dot.className = 'w-2 h-2 rounded-full bg-blue-500';
          txt.innerText = `Hoàn tất cào [${st.sport}] (${st.date_from} → ${st.date_to})`;
          txt.className = 'font-bold text-blue-400';
          stopBtn.classList.add('hidden');
        }

        pill.innerText = `Đã lưu: ${st.total_saved || 0} trận • ${st.total_requests || 0} req (${st.delay_sec || 1}s/req)`;
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
