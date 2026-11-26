<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SportsMatch;
use App\Models\UserDeviceToken;
use App\Models\UserFavorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserAppController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/favorites
     */
    public function favorites(Request $request): JsonResponse
    {
        $query = $request->user()->favorites()->orderByDesc('id');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($sport = $request->query('sport')) {
            $query->where('sport', $sport);
        }

        $items = $query->get();

        return $this->ok($items, [
            'count' => $items->count(),
        ]);
    }

    /**
     * POST /api/v1/favorites
     */
    public function addFavorite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(UserFavorite::TYPES)],
            'sport' => ['required', Rule::in(array_keys(SportsMatch::SPORTS))],
            'entity_id' => ['required', 'string', 'max:120'],
            'name' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
        ]);

        $fav = UserFavorite::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'type' => $validated['type'],
                'sport' => $validated['sport'],
                'entity_id' => $validated['entity_id'],
            ],
            [
                'name' => $validated['name'] ?? null,
                'meta' => $validated['meta'] ?? null,
            ]
        );

        return $this->ok($fav, [], 'Đã thêm vào danh sách yêu thích.', 201);
    }

    /**
     * POST /api/v1/favorites/toggle
     */
    public function toggleFavorite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(UserFavorite::TYPES)],
            'sport' => ['required', Rule::in(array_keys(SportsMatch::SPORTS))],
            'entity_id' => ['required', 'string', 'max:120'],
            'name' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
        ]);

        $existing = UserFavorite::where([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'sport' => $validated['sport'],
            'entity_id' => $validated['entity_id'],
        ])->first();

        if ($existing) {
            $existing->delete();
            return $this->ok([
                'favorited' => false,
                'favorite' => null,
            ], [], 'Đã bỏ khỏi danh sách yêu thích.');
        }

        $fav = UserFavorite::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'sport' => $validated['sport'],
            'entity_id' => $validated['entity_id'],
            'name' => $validated['name'] ?? null,
            'meta' => $validated['meta'] ?? null,
        ]);

        return $this->ok([
            'favorited' => true,
            'favorite' => $fav,
        ], [], 'Đã thêm vào danh sách yêu thích.');
    }

    /**
     * DELETE /api/v1/favorites/{id}
     */
    public function deleteFavorite(Request $request, int $id): JsonResponse
    {
        $deleted = $request->user()->favorites()->where('id', $id)->delete();

        if (!$deleted) {
            return $this->fail('Không tìm thấy mục yêu thích.', 404);
        }

        return $this->ok(null, [], 'Đã xóa khỏi danh sách yêu thích.');
    }

    /**
     * POST /api/v1/devices/register
     * Supports both Guest and Authenticated App Users (via api.auth:optional).
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => ['required', 'string', 'min:10', 'max:512'],
            'platform' => ['required', Rule::in(UserDeviceToken::PLATFORMS)],
            'device_name' => ['nullable', 'string', 'max:120'],
            'push_enabled' => ['nullable', 'boolean'],
        ]);

        $device = UserDeviceToken::updateOrCreate(
            ['device_token' => $validated['device_token']],
            [
                'user_id' => $request->user()?->id,
                'platform' => $validated['platform'],
                'device_name' => $validated['device_name'] ?? null,
                'push_enabled' => $validated['push_enabled'] ?? true,
                'last_active_at' => now(),
            ]
        );

        return $this->ok($device, [], 'Đã đăng ký thiết bị nhận thông báo đẩy.');
    }

    /**
     * DELETE /api/v1/devices/{token}
     */
    public function unregisterDevice(Request $request, string $token): JsonResponse
    {
        $query = UserDeviceToken::where('device_token', $token);
        if ($request->user()) {
            $query->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)->orWhereNull('user_id');
            });
        }

        $deleted = $query->delete();
        if (!$deleted) {
            return $this->fail('Không tìm thấy device token.', 404);
        }

        return $this->ok(null, [], 'Đã hủy đăng ký thiết bị.');
    }
}
