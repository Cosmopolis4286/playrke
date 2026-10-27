<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para notificaciones de usuario
 *
 * Almacena notificaciones persistente que no se pierden al refrescar
 * la página y pueden ser marcadas como leídas.
 *
 *
 * @property string         $id         ID único de la notificación
 * @property string         $user_id    ID del usuario destinatario
 * @property string         $type       Tipo de notificación (wallet_updated, game_alert, etc.)
 * @property string         $title      Título de la notificación
 * @property string         $message    Mensaje de la notificación
 * @property array          $data       Datos adicionales en formato JSON
 * @property bool           $is_read    Si la notificación ha sido leída
 * @property \Carbon\Carbon $read_at    Fecha cuando fue marcada como leída
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 * @property User           $user       Usuario destinatario
 */
class Notification extends Model
{
    use \App\Traits\SnowflakeId;

    /**
     * Tipos de notificación disponibles
     */
    public const TYPES = [
        'WALLET_UPDATED' => 'Actualización de Billetera',
        'WALLET_ADMIN_ALERT' => 'Movimiento Pendiente (Admin)',
        'GAME_ALERT' => 'Alerta de Juego',
        'TRIVIA_REGISTRATION' => 'Inscripción a Trivia',
        'SYSTEM' => 'Sistema',
        'PROMOTION' => 'Promoción',
        'LEVEL_UP' => 'Subida de Nivel',
        'ACHIEVEMENT_UNLOCKED' => 'Logro Desbloqueado',
        'MISSION_COMPLETED' => 'Misión Diaria Completada',
        'EXCHANGE_RATE' => 'Tasa de Cambio',
    ];

    /**
     * Indica si el ID es auto-incremental
     */
    public $incrementing = false;

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
     * Tipo de la clave primaria
     */
    protected $keyType = 'string';

    /**
     * Clave primaria del modelo
     */
    protected $primaryKey = 'id';

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'notifications';

    /**
     * Obtener el color según el tipo de notificación
     */
    public function getTypeColor() : string
    {
        return match ($this->type) {
            'WALLET_UPDATED' => '#28a745', // verde
            'GAME_ALERT' => '#ffc107', // amarillo
            'TRIVIA_REGISTRATION' => '#8b5cf6', // violeta
            'SYSTEM' => '#17a2b8', // cyan
            'PROMOTION' => '#6f42c1', // púrpura
            'LEVEL_UP' => '#667eea', // azul-violeta
            'ACHIEVEMENT_UNLOCKED' => '#f59e0b', // dorado
            'MISSION_COMPLETED' => '#11998e', // verde-azulado
            'BINGO_CARD_PURCHASE' => '#2af598', // verde bingo
            'EXCHANGE_RATE' => '#3b82f6', // azul
            default => '#6c757d', // gris
        };
    }

    /**
     * Obtener la descripción del tipo de notificación
     */
    public function getTypeDescription() : string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Obtener el icono según el tipo de notificación
     */
    public function getTypeIcon() : string
    {
        return match ($this->type) {
            'WALLET_UPDATED' => '💰',
            'GAME_ALERT' => '🎮',
            'TRIVIA_REGISTRATION' => '🎯',
            'SYSTEM' => '⚙️',
            'PROMOTION' => '🎁',
            'LEVEL_UP' => '⬆️',
            'ACHIEVEMENT_UNLOCKED' => '🏆',
            'MISSION_COMPLETED' => '🎯',
            'BINGO_CARD_PURCHASE' => '🎟️',
            default => '📢',
        };
    }

    /**
     * Verificar si la notificación es de tipo wallet
     */
    public function isWalletNotification() : bool
    {
        return $this->type === 'WALLET_UPDATED';
    }

    /**
     * Marcar la notificación como leída
     */
    public function markAsRead() : bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeByType(Builder $query, string $type) : Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para obtener las notificaciones más recientes primero
     */
    public function scopeLatest(Builder $query) : Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope para obtener notificaciones leídas
     */
    public function scopeRead(Builder $query) : Builder
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope para obtener notificaciones no leídas
     */
    public function scopeUnread(Builder $query) : Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Relación con el usuario destinatario
     *
     * @return BelongsTo<User, Notification>
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
