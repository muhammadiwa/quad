<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class QuadrangSetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('quadrang_settings'));
        static::deleted(fn () => Cache::forget('quadrang_settings'));
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $settings = Cache::rememberForever('quadrang_settings', fn () => static::pluck('value', 'key')->all());

        return $settings[$key] ?? $default;
    }

    public static function set(string $key, ?string $value, ?string $description = null): void
    {
        $attributes = ['value' => $value];
        if ($description !== null) {
            $attributes['description'] = $description;
        }

        static::updateOrCreate(
            ['key' => $key],
            $attributes,
        );
    }
}
