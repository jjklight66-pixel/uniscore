<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next, string $mode = 'required'): Response
    {
        $bearer = $request->bearerToken();

        if (!$bearer) {
            if ($mode === 'optional') {
                return $next($request);
            }

            return response()->json([
                'success' => false,
                'message' => 'Thiếu Bearer Token xác thực. Vui lòng gửi header Authorization: Bearer <token>.',
                'data' => null,
            ], 401);
        }

        $tokenModel = ApiToken::findValid($bearer);

        if (!$tokenModel || !$tokenModel->user) {
            if ($mode === 'optional') {
                return $next($request);
            }

            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.',
                'data' => null,
            ], 401);
        }

        $tokenModel->forceFill(['last_used_at' => now()])->saveQuietly();
        $request->setUserResolver(fn () => $tokenModel->user);
        $request->attributes->set('api_token', $tokenModel);

        return $next($request);
    }
}
