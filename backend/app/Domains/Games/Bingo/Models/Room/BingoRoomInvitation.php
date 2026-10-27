<?php

namespace App\Domains\Games\Bingo\Models\Room;

use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Invitado a una sala VIP: un jugador registrado (user_id) o un comprador
 * externo identificado por teléfono (phone/phone_key).
 */
class BingoRoomInvitation extends Model
{
    use SnowflakeId;

    protected $casts = [
        'id' => 'string',
        'room_id' => 'string',
        'user_id' => 'string',
        'invited_by_user_id' => 'string',
    ];

    protected $fillable = ['room_id', 'user_id', 'phone', 'phone_key', 'label', 'invited_by_user_id'];

    protected $table = 'bingo_room_invitations';

    /**
     * Clave de comparación de un teléfono: sus últimos 10 dígitos. Los
     * teléfonos llegan con formatos distintos ("+58 0414-1234567",
     * "+584141234567", "04141234567") y todos comparten esos 10 dígitos.
     * null si no tiene al menos 7 dígitos (no es un teléfono usable).
     */
    public static function phoneKey(?string $phone) : ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return strlen($digits) >= 7 ? substr($digits, -10) : null;
    }

    public function room() : BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
