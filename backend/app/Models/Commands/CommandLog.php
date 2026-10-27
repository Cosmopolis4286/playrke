<?php

namespace App\Models\Commands;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo CommandLog refactorizado
 *
 * Solo contiene:
 * - Configuración de la tabla y propiedades
 * - Boot methods para Snowflake
 * - Accessors y Mutators si es necesario
 * - Scopes útiles
 *
 * NOTA: Las consultas complejas se han movido a CommandLogRepository
 */
class CommandLog extends Model
{
    use \App\Traits\SnowflakeId;
    public $incrementing = false;

    // ========================
    // Casting de tipos
    // ========================
    protected $casts = [
        'executed_at' => 'datetime',
        'status' => 'string',
        'triggered_by' => 'string',
    ];

    // ========================
    // Campos asignables
    // ========================
    protected $fillable = [
        'command',
        'output',
        'status',
        'executed_at',
        'triggered_by',
        'triggered_by_user_id',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    // ========================
    // Configuración de la tabla
    // ========================
    protected $table = 'command_logs';

    /**
     * Verificar si el comando tuvo error
     */
    public function getIsErrorAttribute() : bool
    {
        return $this->status === 'error';
    }

    // ========================
    // Accessors
    // ========================

    /**
     * Verificar si el comando fue exitoso
     */
    public function getIsSuccessfulAttribute() : bool
    {
        return $this->status === 'success';
    }

    /**
     * Obtener output truncado para vista
     */
    public function getTruncatedOutputAttribute() : string
    {
        if (! $this->output) {
            return '';
        }

        return strlen($this->output) > 100
            ? substr($this->output, 0, 100) . '...'
            : $this->output;
    }

    /**
     * Scope para buscar por comando
     */
    public function scopeByCommand($query, string $command)
    {
        return $query->where('command', $command);
    }

    /**
     * Scope para comandos con error
     */
    public function scopeErrors($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope para ordenar por fecha de ejecución
     */
    public function scopeOrderedByExecution($query, string $direction = 'desc')
    {
        return $query->orderBy('executed_at', $direction);
    }

    /**
     * Scope para comandos recientes
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('executed_at', '>=', now()->subDays($days));
    }

    // ========================
    // Scopes útiles
    // ========================

    /**
     * Scope para comandos exitosos
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function triggeredByUser() : BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
