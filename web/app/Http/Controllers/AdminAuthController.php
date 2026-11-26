<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()?->isAdmin()) {
            return redirect()->route('admin.index');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (!Auth::user()?->isAdmin()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('admin.login')->withErrors([
                    'email' => 'Tài khoản này là tài khoản người dùng App, không có quyền quản trị.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            return redirect()->intended(route('admin.index'))->with('success', 'Đăng nhập quản trị thành công!');
        }

        return redirect()->route('admin.login')->withErrors([
            'email' => 'Email hoặc mật khẩu quản trị không chính xác.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login')->with('success', 'Đã đăng xuất khỏi trang quản trị.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
        ]);

        $user = $request->user();
        if (!$user || !Hash::check($validated['current_password'], $user->password)) {
            return redirect()->to(route('admin.index') . '#section-settings')
                ->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.']);
        }

        $user->update(['password' => $validated['password']]);

        return redirect()->to(route('admin.index') . '#section-settings')
            ->with('success', 'Đã đổi mật khẩu quản trị thành công!');
    }
}
