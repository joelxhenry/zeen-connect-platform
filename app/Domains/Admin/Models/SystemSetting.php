<?php

namespace App\Domains\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("system_setting.{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, ?string $description = null): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ]
        );

        Cache::forget("system_setting.{$key}");

        return $setting;
    }

    /**
     * Clear the cache for a specific setting.
     */
    public static function clearCache(string $key): void
    {
        Cache::forget("system_setting.{$key}");
    }

    /**
     * Clear all settings cache.
     */
    public static function clearAllCache(): void
    {
        $settings = static::all();

        foreach ($settings as $setting) {
            Cache::forget("system_setting.{$setting->key}");
        }
    }
}
