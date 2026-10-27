<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audit extends Model
{
    protected $casts = [
        'auditable_id' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
    ];

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
    protected $table = 'audits';

    /** Nombre legible de la clase auditada (sin namespace) */
    public function getAuditableNameAttribute() : string
    {
        if (! $this->auditable_type) {
            return '—';
        }
        $parts = explode('\\', $this->auditable_type);

        return end($parts);
    }

    public function scopeForUser(Builder $query, int|string $userId) : Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfActor(Builder $query, string $actorType) : Builder
    {
        return $query->where('actor_type', $actorType);
    }

    /**
     * Filtra por la instancia auditada (auditable_type + auditable_id).
     *
     * auditable_id se guarda vía Auditable::serializeValue() como JSON: para
     * un ID simple (string/int) el valor queda como escalar JSON (ej. "123"),
     * no como el string plano "123" — comparar con where() normal contra la
     * columna JSON no matchea (MySQL normaliza el JSON almacenado). Se
     * compara con JSON_UNQUOTE() para obtener el valor escalar real.
     */
    public function scopeOfAuditable(Builder $query, string $modelClass, int|string $id) : Builder
    {
        return $query
            ->where('auditable_type', $modelClass)
            ->whereRaw('JSON_UNQUOTE(auditable_id) = ?', [(string) $id]);
    }

    public function scopeOfEvent(Builder $query, string $event) : Builder
    {
        return $query->where('event', $event);
    }

    public function scopeOfModel(Builder $query, string $modelClass) : Builder
    {
        return $query->where('auditable_type', $modelClass);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
