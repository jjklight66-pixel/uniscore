<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng Nhập Quản Trị - UNISCORE ADMIN</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { color-scheme: dark; }
    body { background-color: #0b0d12; color: #d1d5db; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    input { background-color: #0f1219 !important; color: #f3f4f6 !important; border-color: #2a3040 !important; }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-md bg-[#141720] border border-[#232836] rounded-2xl p-7 shadow-2xl space-y-6">
    <div class="text-center space-y-1">
      <div class="text-3xl font-black tracking-tight">
        <span class="text-[#3b82f6]">UNI</span><span class="text-[#f59e0b]">SCORE</span>
      </div>
      <h1 class="text-sm font-bold text-white uppercase tracking-wider">Đăng Nhập Quản Trị Viên (Admin)</h1>
      <p class="text-xs text-gray-400">Vui lòng xác thực để truy cập trung tâm điều khiển dữ liệu</p>
    </div>

    @if(session('success'))
      <div class="bg-emerald-950/60 border border-emerald-700 text-emerald-300 px-3.5 py-2.5 rounded-lg text-xs">
        ✅ {{ session('success') }}
      </div>
    @endif

    @if($errors->any())
      <div class="bg-red-950/60 border border-red-800 text-red-300 px-3.5 py-2.5 rounded-lg text-xs">
        ❌ {{ $errors->first() }}
      </div>
    @endif

    <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-4 text-xs">
      @csrf
      <div>
        <label class="block text-gray-400 mb-1.5 font-semibold">Email quản trị:</label>
        <input type="email" name="email" value="{{ old('email', 'admin@uniscore.local') }}" required autofocus
               class="w-full px-3.5 py-2.5 rounded-lg border focus:outline-none focus:border-[#f59e0b]">
      </div>

      <div>
        <label class="block text-gray-400 mb-1.5 font-semibold">Mật khẩu:</label>
        <input type="password" name="password" value="" placeholder="••••••••" required
               class="w-full px-3.5 py-2.5 rounded-lg border focus:outline-none focus:border-[#f59e0b]">
      </div>

      <label class="flex items-center gap-2 text-gray-400 cursor-pointer">
        <input type="checkbox" name="remember" checked class="accent-[#f59e0b]">
        <span>Ghi nhớ đăng nhập</span>
      </label>

      <button type="submit" class="w-full bg-[#f59e0b] hover:bg-amber-600 text-black font-black py-2.5 rounded-lg transition shadow-lg text-sm">
        🔐 ĐĂNG NHẬP ADMIN
      </button>
    </form>

    <div class="pt-4 border-t border-[#232836] flex items-center justify-between text-xs text-gray-500">
      <a href="{{ route('home') }}" class="hover:text-white transition">← Quay lại Trang chủ</a>
      <span>Tài khoản mặc định: <b class="text-gray-300">admin@uniscore.local</b></span>
    </div>
  </div>
</body>
</html>
