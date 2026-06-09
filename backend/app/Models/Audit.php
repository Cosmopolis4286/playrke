<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Audit extends Model
{
    protected $table = 'audits';

    protected $fillable = [
        'user_id',
        'actor_type',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'auditable_id' => 'array',
        'old_values'   => 'array',
        'new_values'   => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeOfEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    public function scopeOfActor(Builder $query, string $actorType): Builder
    {
        return $query->where('actor_type', $actorType);
    }

    public function scopeOfModel(Builder $query, string $modelClass): Builder
    {
        return $query->where('auditable_type', $modelClass);
    }

    public function scopeForUser(Builder $query, int|string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /** Nombre legible de la clase auditada (sin namespace) */
    public function getAuditableNameAttribute(): string
    {
        if (!$this->auditable_type) {
            return '—';
        }
        $parts = explode('\\', $this->auditable_type);
        return end($parts);
    }
}
