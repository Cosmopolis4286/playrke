<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Module extends Model
{
    use Auditable, SnowflakeId;

    public const CACHE_KEY = 'modules:active-map';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $fillable = [
        'key',
        'label',
        'is_active',
    ];

    /**
     * ¿Está activo el módulo? Sin fila = activo: la tabla es una lista de
     * excepciones a apagar (mismo criterio que SidebarMenuService).
     */
    public static function isActive(string $key) : bool
    {
        $map = Cache::remember(self::CACHE_KEY, 300, fn () => static::query()->pluck('is_active', 'key')->all());

        return (bool) ($map[$key] ?? true);
    }

    protected static function booted() : void
    {
        // El toggle del superadmin debe aplicarse en la siguiente request.
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }
}
