<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'updated_by'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    private const CACHE = 'settings:all';

    /** All settings as [key => value], cached until the next write. */
    public static function cached(): array
    {
        return Cache::rememberForever(self::CACHE, fn () => static::query()->pluck('value', 'key')
            ->map(fn ($v) => is_string($v) ? json_decode($v, true) : $v)->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::cached()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value, ?int $userId = null): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $userId]);
        Cache::forget(self::CACHE);
    }
}
