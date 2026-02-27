<?php

namespace App\Models\Roles;

use App\Models\BaseModel;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Game;
use App\Models\Pivots\Roles\Treasurer\TreasurerCurrency;
use App\Models\Pivots\Roles\Treasurer\TreasurerGame;
use App\Models\Shared\UniqueCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo para tesoreros del sistema.
 */
class Treasurer extends BaseModel
{
    protected $table = 'treasurers';

    protected $fillable = [
        'name',
        'country_id',
        'phone',
        'commission',
        'user_id',
        'unique_code_id',
        'status',
    ];

    protected $casts = [
        'id'             => 'string',
        'country_id'     => 'string',
        'user_id'        => 'string',
        'unique_code_id' => 'string',
        'commission'     => 'decimal:2',
        'status'         => 'string',
    ];

    // ========================
    // Accessors computados
    // ========================
    protected $appends = ['status_badge'];

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'ACTIVE'   => '<span class="badge badge-success">Activo</span>',
            'INACTIVE' => '<span class="badge badge-secondary">Inactivo</span>',
            'BLOCKED'  => '<span class="badge badge-danger">Bloqueado</span>',
            'DELETED'  => '<span class="badge badge-dark">Eliminado</span>',
            default    => '<span class="badge badge-warning">Desconocido</span>',
        };
    }

    // ========================
    // Relaciones
    // ========================

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uniqueCode(): BelongsTo
    {
        return $this->belongsTo(UniqueCode::class, 'unique_code_id');
    }

    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Currency::class,
            'treasurers_currencies',
            'treasurer_id',
            'currency_id'
        )
            ->using(TreasurerCurrency::class)
            ->withPivot(['level', 'status'])
            ->withTimestamps();
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(
            Game::class,
            'treasurers_games',
            'treasurer_id',
            'game_id'
        )
            ->using(TreasurerGame::class)
            ->withPivot('status')
            ->withTimestamps();
    }

    public function currenciesActive(): BelongsToMany
    {
        return $this->currencies()
            ->wherePivot('status', 'ACTIVE');
    }

    public function gamesActive(): BelongsToMany
    {
        return $this->games()
            ->wherePivot('status', 'ACTIVE');
    }

    // ========================
    // Helpers estáticos
    // ========================

    public static function getValueByUserId(string|int $userId, string $field): mixed
    {
        return static::where('user_id', $userId)->value($field);
    }
}
