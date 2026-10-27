<?php

namespace App\Models\Commands;

use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de control por comando programado (routes/console.php) — is_enabled
 * gobierna únicamente si el scheduler automático lo corre (ver
 * App\Support\Scheduling\ScheduledCommandGate), no bloquea invocación manual.
 *
 * cron_expression es la cadencia real, leída en runtime por
 * ScheduledCommandGate::cronFor() — cuando es NULL, console.php usa el
 * método fijo original (->everyMinute(), ->hourly(), etc.) como fallback.
 *
 * Auditable: toggle()/updateFrequency()/resetFrequency() en
 * ScheduledCommandService ya hacen $model->update(...), así que quedan
 * auditados automáticamente (tabla audits, visible en /audit) sin tocar el
 * servicio. No se marca 'updated' en $auditAlertEvents (no dispara
 * Log::critical hacia /logs) porque incluiría cambios de frecuencia
 * rutinarios junto con el toggle real — demasiado ruidoso para una alerta.
 */
class ScheduledCommand extends Model
{
    use \App\Traits\SnowflakeId;
    use Auditable;
    public $incrementing = false;

    protected $casts = [
        'is_enabled' => 'boolean',
        'disabled_at' => 'datetime',
        'disabled_by' => 'string',
        'min_interval_seconds' => 'integer',
        'frequency_updated_by' => 'string',
        'frequency_updated_at' => 'datetime',
    ];

    protected $fillable = [
        'command',
        'label',
        'description',
        'is_enabled',
        'disabled_at',
        'disabled_by',
        'cron_expression',
        'min_interval_seconds',
        'frequency_updated_by',
        'frequency_updated_at',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'scheduled_commands';

    public function disabledBy() : BelongsTo
    {
        return $this->belongsTo(User::class, 'disabled_by');
    }

    public function frequencyUpdatedBy() : BelongsTo
    {
        return $this->belongsTo(User::class, 'frequency_updated_by');
    }

    public function scopeByCommand($query, string $command)
    {
        return $query->where('command', $command);
    }

    public function scopeDisabled($query)
    {
        return $query->where('is_enabled', false);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
