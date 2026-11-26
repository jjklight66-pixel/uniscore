<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập quản trị — UniScore</title>
  @include('partials.fl-head')
</head>
<body class="fl min-h-screen flex items-center justify-center p-4">
  <main class="w-full max-w-md fl-panel p-7 sm:p-8 space-y-6">
    <div class="text-center space-y-2">
      <div class="font-score text-4xl font-extrabold tracking-tight leading-none select-none">
        <span class="text-frost">UNI</span><span class="text-live">SCORE</span>
      </div>
      <h1 class="text-base font-semibold text-frost">Đăng nhập quản trị</h1>
      <p class="text-xs fl-muted">Xác thực tài khoản để truy cập bảng điều khiển dữ liệu.</p>
    </div>

    @if(session('success'))
      <div class="border border-goal/40 bg-goal/10 text-goal px-3.5 py-2.5 rounded text-xs font-medium">
        {{ session('success') }}
      </div>
    @endif

    @if($errors->any())
      <div class="border border-alert/50 bg-alert/10 text-[#FF8A8D] px-3.5 py-2.5 rounded text-xs font-medium">
        {{ $errors->first() }}
      </div>
    @endif

    <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-4">
      @csrf
      <div>
        <label for="loginEmail" class="block text-xs font-medium text-mist mb-1.5">Email quản trị</label>
        <input id="loginEmail" type="email" name="email" value="{{ old('email', 'admin@uniscore.local') }}" required autofocus
               class="fl-input w-full py-2.5 px-3">
      </div>

      <div>
        <label for="loginPassword" class="block text-xs font-medium text-mist mb-1.5">Mật khẩu</label>
        <input id="loginPassword" type="password" name="password" value="" placeholder="••••••••" required
               class="fl-input w-full py-2.5 px-3">
      </div>

      <label class="flex items-center gap-2 text-xs text-mist cursor-pointer select-none">
        <input type="checkbox" name="remember" checked class="accent-live rounded">
        <span>Ghi nhớ đăng nhập</span>
      </label>

      <button type="submit" class="fl-btn fl-btn--primary w-full py-2.5 text-sm">
        Đăng nhập
      </button>
    </form>

    <div class="pt-4 border-t border-seat flex items-center justify-between gap-2 text-xs text-mist">
      <a href="{{ route('home') }}" class="hover:text-frost transition-colors">Trang chủ</a>
      <span>Mặc định: <strong class="text-frost font-medium">admin@uniscore.local</strong></span>
    </div>
  </main>
</body>
</html>
