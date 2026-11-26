<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SportsMatch extends Model
{
    protected $table = 'sports_matches';

    protected $guarded = [];

    protected $casts = [
        'is_pinned' => 'boolean',
        'payload' => 'array',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'priority_order' => 'integer',
    ];

    public const SPORTS = [
        'football' => ['name' => 'Bóng đá', 'icon' => '⚽'],
        'basketball' => ['name' => 'Bóng rổ', 'icon' => '🏀'],
        'tennis' => ['name' => 'Quần vợt', 'icon' => '🎾'],
        'american_football' => ['name' => 'Bóng bầu dục Mỹ', 'icon' => '🏈'],
        'baseball' => ['name' => 'Bóng chày', 'icon' => '⚾'],
        'badminton' => ['name' => 'Cầu lông', 'icon' => '🏸'],
        'volleyball' => ['name' => 'Bóng chuyền', 'icon' => '🏐'],
        'cricket' => ['name' => 'Cricket', 'icon' => '🏏'],
        'ice_hockey' => ['name' => 'Khúc côn cầu trên băng', 'icon' => '🏒'],
        'table_tennis' => ['name' => 'Bóng bàn', 'icon' => '🏓'],
    ];
}
