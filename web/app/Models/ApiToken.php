<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiToken extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = ['token'];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Issue a new plaintext Bearer token and store its SHA-256 hash.
     *
     * @return array{plain_text_token: string, model: self}
     */
    public static function issue(User $user, string $name = 'mobile-app', ?int $ttlDays = 90): array
    {
        $plain = 'usc_' . bin2hex(random_bytes(32));
        $model = self::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plain),
            'last_used_at' => now(),
            'expires_at' => $ttlDays ? now()->addDays($ttlDays) : null,
        ]);

        return [
            'plain_text_token' => $plain,
            'model' => $model,
        ];
    }

    public static function findValid(string $plainTextToken): ?self
    {
        $hashed = hash('sha256', trim($plainTextToken));
        $token = self::with('user')->where('token', $hashed)->first();
        if (!$token) {
            return null;
        }
        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();
            return null;
        }
        return $token;
    }
}
