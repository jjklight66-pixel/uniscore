<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'site_name' => 'UNISCORE',
        'site_tagline' => 'Tỷ số trực tiếp 10 môn thể thao',
        'site_logo_url' => '',
        'site_favicon_url' => '',
        'seo_description' => 'Cập nhật tỷ số trực tiếp, lịch thi đấu, bảng xếp hạng và thống kê chi tiết 10 môn thể thao theo thời gian thực.',
        'notice_banner' => '',
        'footer_text' => 'Nền tảng dữ liệu thể thao trực tiếp 10 môn.',
        'contact_email' => '',
        'support_url' => '',
        'mission_control_url' => 'http://localhost:8501',
        'show_mission_control' => '1',
        'default_sport' => 'football',
    ];

    public static function allCached(): array
    {
        try {
            if (!Schema::hasTable('site_settings')) {
                return self::DEFAULTS;
            }

            $stored = Cache::remember('site_settings_all', 3600, function () {
                return self::pluck('value', 'key')->toArray();
            });

            return array_merge(self::DEFAULTS, $stored);
        } catch (\Throwable $e) {
            return self::DEFAULTS;
        }
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $all = self::allCached();
        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return (string) $all[$key];
        }
        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            if (!array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            self::updateOrCreate(
                ['key' => $key],
                ['value' => $value === null ? '' : (string) $value]
            );
        }
        Cache::forget('site_settings_all');
    }
}
