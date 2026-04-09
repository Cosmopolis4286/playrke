<?php

declare(strict_types=1);

namespace App\Domains\Trivia\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo: Sala de Trivia (trivia_rooms).
 * 
 * Representa una sala/sesión de juego en la base de datos.
 * El estado en tiempo real se maneja en Redis, no aquí.
 */
class TriviaRoom extends BaseModel
{
    protected $table = 'trivia_rooms';

    protected $fillable = [
        'code',
        'title',
        'description',
        'host_id',
        'status', // 'waiting', 'playing', 'closed', 'cancelled'
        'max_players',
        'question_count',
        'time_per_question',
        'scheduled_at',
        'started_at',
        'ended_at',
        'settings', // JSON
    ];

    protected $casts = [
        'settings' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'max_players' => 'integer',
        'question_count' => 'integer',
        'time_per_question' => 'integer',
    ];

    /**
     * Relación: Juegos en esta sala.
     */
    public function games(): HasMany
    {
        return $this->hasMany(TriviaGame::class, 'room_id');
    }

    /**
     * Relación: Preguntas de esta sala.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(TriviaQuestion::class, 'room_id');
    }

    /**
     * Scope: Salas disponibles para unirse.
     */
    public function scopeJoinable($query)
    {
        return $query->where('status', 'waiting')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now()->addMinutes(5));
            });
    }

    /**
     * Verificar si la sala puede iniciarse.
     */
    public function canStart(): bool
    {
        return $this->status === 'waiting' &&
               $this->games()->where('status', 'active')->doesntExist();
    }

    /**
     * Verificar si está activa.
     */
    public function isActive(): bool
    {
        return $this->status === 'playing';
    }
}
