<?php

namespace App\Traits;

/**
 * Scope y helper compartidos por catálogos con columna `status`
 * ACTIVE/INACTIVE (GameMode, PrizePoolType, ConnectionType, RoomType).
 */
trait HasActiveStatusScope
{
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function isInactive(): bool
    {
        return $this->status === 'INACTIVE';
    }
}
