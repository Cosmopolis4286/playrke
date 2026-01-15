<?php

namespace App\Models\Roles\Treasurer;

use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para la gestión de otros tesoreros.
 *
 * Representa a los tesoreros adicionales con permisos específicos
 * para juegos y monedas en el sistema.
 */
class OtherTreasurers extends Model
{
    use SnowflakeId;

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'creator_user_id',     // ID del usuario creador del tesorero
        'allowed_games',       // Array JSON de IDs de juegos permitidos
        'allowed_currencies',  // Array JSON de IDs de monedas permitidas
        'status',             // Estado del tesorero (ACTIVE, INACTIVE, BLOCKED)
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allowed_games' => 'array',
        'allowed_currencies' => 'array',
    ];

    /**
     * Relación con el usuario creador.
     *
     * @return BelongsTo
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Scope para filtrar por estado.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para obtener tesoreros activos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope para filtrar por usuario creador.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $creatorUserId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCreatorUser($query, string $creatorUserId)
    {
        return $query->where('creator_user_id', $creatorUserId);
    }

    /**
     * Scope para tesoreros con acceso a un juego específico.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $gameId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithGameAccess($query, string $gameId)
    {
        return $query->whereJsonContains('allowed_games', $gameId);
    }

    /**
     * Scope para tesoreros con acceso a una moneda específica.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $currencyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCurrencyAccess($query, string $currencyId)
    {
        return $query->whereJsonContains('allowed_currencies', $currencyId);
    }

    /**
     * Verificar si el tesorero tiene acceso a un juego específico.
     *
     * @param string $gameId ID del juego
     * @return bool
     */
    public function hasGameAccess(string $gameId): bool
    {
        return in_array($gameId, $this->allowed_games ?? []);
    }

    /**
     * Verificar si el tesorero tiene acceso a una moneda específica.
     *
     * @param string $currencyId ID de la moneda
     * @return bool
     */
    public function hasCurrencyAccess(string $currencyId): bool
    {
        return in_array($currencyId, $this->allowed_currencies ?? []);
    }

    /**
     * Verificar si el tesorero está activo.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verificar si el tesorero está bloqueado.
     *
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->status === 'BLOCKED';
    }
}
