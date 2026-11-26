<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:120'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'password' => $validated['password'],
        ]);

        $issued = ApiToken::issue($user, $validated['device_name'] ?? 'mobile-app');

        return $this->ok([
            'token' => $issued['plain_text_token'],
            'token_type' => 'Bearer',
            'expires_at' => $issued['model']->expires_at?->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ], [], 'Đăng ký tài khoản thành công.', 201);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::where('email', strtolower(trim($validated['email'])))->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->fail('Email hoặc mật khẩu không chính xác.', 401);
        }

        $issued = ApiToken::issue($user, $validated['device_name'] ?? 'mobile-app');

        return $this->ok([
            'token' => $issued['plain_text_token'],
            'token_type' => 'Bearer',
            'expires_at' => $issued['model']->expires_at?->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ], [], 'Đăng nhập thành công.');
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->ok([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'favorites_count' => $user->favorites()->count(),
            'devices_count' => $user->deviceTokens()->count(),
            'created_at' => $user->created_at?->toIso8601String(),
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        if ($request->boolean('all_devices', false)) {
            $request->user()->apiTokens()->delete();
            return $this->ok(null, [], 'Đã đăng xuất khỏi tất cả thiết bị.');
        }

        /** @var ApiToken|null $tokenModel */
        $tokenModel = $request->attributes->get('api_token');
        $tokenModel?->delete();

        return $this->ok(null, [], 'Đăng xuất thành công.');
    }
}
