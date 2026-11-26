<?php

use App\Models\SportsMatch;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự động đồng bộ lịch thi đấu hôm nay & ngày mai vào 01:00 sáng mỗi ngày
Schedule::call(function () {
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    foreach (array_keys(SportsMatch::SPORTS) as $sport) {
        Artisan::call('sports:crawl-range', [
            'sport' => $sport,
            '--from' => $today,
            '--to' => $tomorrow,
            '--delay' => 1.0,
        ]);
    }
})->dailyAt('01:00')->name('daily-sports-crawl')->withoutOverlapping();
