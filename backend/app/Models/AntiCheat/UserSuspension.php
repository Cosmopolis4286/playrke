<?php

namespace App\Models\AntiCheat;

use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSuspension extends Model
{
    use SnowflakeId;

    protected $table = 'user_suspensions';

    public const ORIGIN_ANTI_CHEAT   = 'anti_cheat';
    public const ORIGIN_MANUAL_ADMIN = 'manual_admin';

    protected $fillable = [
        'user_id', 'origin', 'reason', 'notes', 'triggering_flag_id',
        'suspended_by_user_id', 'expires_at', 'resolved_at',
        'resolved_by_user_id', 'resolution_notes',
    ];

    protected $casts = [
        'id'                    => 'string',
        'user_id'               => 'string',
        'triggering_flag_id'    => 'string',
        'suspended_by_user_id'  => 'string',
        'resolved_by_user_id'   => 'string',
        'expires_at'            => 'datetime',
        'resolved_at'           => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function triggeringFlag(): BelongsTo
    {
        return $this->belongsTo(AntiCheatFlag::class, 'triggering_flag_id');
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function isActive(): bool
    {
        return $this->resolved_at === null;
    }

    /**
     * Resuelve la suspensión: registra quién y por qué, y reactiva al usuario
     * (revierte User::status a ACTIVE). No revierte si el usuario tiene otra
     * suspensión activa distinta a esta.
     */
    public function resolve(User $admin, string $notes): bool
    {
        $this->resolved_at         = now();
        $this->resolved_by_user_id = $admin->id;
        $this->resolution_notes    = $notes;
        $saved = $this->save();

        $hasOtherActive = self::where('user_id', $this->user_id)
            ->whereNull('resolved_at')
            ->where('id', '!=', $this->id)
            ->exists();

        if (! $hasOtherActive) {
            User::where('id', $this->user_id)->update(['status' => 'ACTIVE']);
        }

        return $saved;
    }
}
