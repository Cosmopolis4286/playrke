<?php

namespace App\Models\Directories;

use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de eventos de dominio sobre registros de directorio de pago
 * (PagoMovil Venezuela, PixKey Brasil) — ver migración create_directory_audits_table.
 */
class DirectoryAudit extends Model
{
    use SnowflakeId;

    public const EVENT_CREATED = 'created';
    public const EVENT_DELETED = 'deleted';
    public const EVENT_STATUS_CHANGED = 'status_changed';
    public const EVENT_UPDATED = 'updated';

    public const TYPE_PAGO_MOVIL = 'pago_movil';
    public const TYPE_PIX_KEY = 'pix_key';

    protected $casts = [
        'record_id' => 'string',
        'owner_user_id' => 'string',
        'actor_user_id' => 'string',
        'is_impersonation' => 'boolean',
        'changes' => 'array',
    ];

    protected $fillable = [
        'record_type',
        'record_id',
        'owner_user_id',
        'actor_user_id',
        'event',
        'actor_role',
        'is_impersonation',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $table = 'directory_audits';

    public function actor() : BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function owner() : BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
