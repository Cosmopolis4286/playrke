<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo para notificaciones de usuario
 *
 * Almacena notificaciones persistente que no se pierden al refrescar
 * la página y pueden ser marcadas como leídas.
 *
 * @package App\Models
 *
 * @property string $id ID único de la notificación
 * @property string $user_id ID del usuario destinatario
 * @property string $type Tipo de notificación (wallet_updated, game_alert, etc.)
 * @property string $title Título de la notificación
 * @property string $message Mensaje de la notificación
 * @property array $data Datos adicionales en formato JSON
 * @property bool $is_read Si la notificación ha sido leída
 * @property \Carbon\Carbon $read_at Fecha cuando fue marcada como leída
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 *
 * @property-read User $user Usuario destinatario
 */
class Notification extends Model
{
    use \App\Traits\SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'notifications';

    /**
     * Clave primaria del modelo
     */
    protected $primaryKey = 'id';

    /**
     * Indica si el ID es auto-incremental
     */
    public $incrementing = false;

    /**
     * Tipo de la clave primaria
     */
    protected $keyType = 'string';

    /**
     * Tipos de notificación disponibles
     */
    public const TYPES = [
        'WALLET_UPDATED'        => 'Actualización de Billetera',
        'WALLET_ADMIN_ALERT'    => 'Movimiento Pendiente (Admin)',
        'GAME_ALERT'            => 'Alerta de Juego',
        'TRIVIA_REGISTRATION'   => 'Inscripción a Trivia',
        'SYSTEM'                => 'Sistema',
        'PROMOTION'             => 'Promoción',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el usuario destinatario
     *
     * @return BelongsTo<User, Notification>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Marcar la notificación como leída
     *
     * @return bool
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Scope para obtener notificaciones no leídas
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope para obtener notificaciones leídas
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope para filtrar por tipo
     *
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para obtener las notificaciones más recientes primero
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Obtener la descripción del tipo de notificación
     *
     * @return string
     */
    public function getTypeDescription(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Verificar si la notificación es de tipo wallet
     *
     * @return bool
     */
    public function isWalletNotification(): bool
    {
        return $this->type === 'WALLET_UPDATED';
    }

    /**
     * Obtener el color según el tipo de notificación
     *
     * @return string
     */
    public function getTypeColor(): string
    {
        return match($this->type) {
            'WALLET_UPDATED' => '#28a745', // verde
            'GAME_ALERT' => '#ffc107', // amarillo
            'TRIVIA_REGISTRATION' => '#8b5cf6', // violeta
            'SYSTEM' => '#17a2b8', // cyan
            'PROMOTION' => '#6f42c1', // púrpura
            default => '#6c757d', // gris
        };
    }

    /**
     * Obtener el icono según el tipo de notificación
     *
     * @return string
     */
    public function getTypeIcon(): string
    {
        return match($this->type) {
            'WALLET_UPDATED' => '💰',
            'GAME_ALERT' => '🎮',
            'TRIVIA_REGISTRATION' => '🎯',
            'SYSTEM' => '⚙️',
            'PROMOTION' => '🎁',
            default => '📢',
        };
    }
}
