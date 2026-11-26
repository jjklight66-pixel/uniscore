<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDeviceToken extends Model
{
    public const PLATFORMS = ['android', 'ios', 'web'];

    protected $fillable = [
        'user_id',
        'device_token',
        'platform',
        'device_name',
        'push_enabled',
        'last_active_at',
    ];

    protected $casts = [
        'push_enabled' => 'boolean',
        'last_active_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
