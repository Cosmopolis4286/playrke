<?php
declare(strict_types=1);
namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAudit;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

/**
 * Registra eventos de dominio sobre salas de bingo en la tabla bingo_room_audits.
 *
 * Uso:
 *   $this->auditLogger->log($room, 'status_changed', $actorRole, 'ACTIVE → CANCELLED', [...])
 */
final class BingoRoomAuditLogger
{
    public function log(
        BingoRoom   $room,
        string      $event,
        string      $actorRole,
        string      $description = '',
        array       $metadata    = [],
        string|null $userId      = null,
    ): void {
        // En contexto CLI (Jobs, Commands) no hay request HTTP — evitar llamada a Request::ip().
        $isHttp = !App::runningInConsole();

        BingoRoomAudit::create([
            'room_id'     => (string) $room->id,
            'user_id'     => $userId,
            'event'       => $event,
            'actor_role'  => $actorRole,
            'description' => $description,
            'metadata'    => $metadata ?: null,
            'ip_address'  => $isHttp ? Request::ip() : null,
            'user_agent'  => $isHttp ? Request::userAgent() : null,
        ]);
    }

    // --- Domain event constants ---
    public const EVENT_STATUS_CHANGED        = 'status_changed';
    public const EVENT_CANCELLED             = 'cancelled';
    public const EVENT_REFUND_INITIATED      = 'refund_initiated';
    public const EVENT_REFUND_COMPLETED      = 'refund_completed';
    public const EVENT_REFUND_FAILED         = 'refund_failed';
    public const EVENT_DRAW_STARTED          = 'draw_started';
    public const EVENT_DRAW_FINISHED         = 'draw_finished';
    public const EVENT_WINNER_DECLARED       = 'winner_declared';
    public const EVENT_CREATED_UNSCHEDULED   = 'created_unscheduled';
    public const EVENT_MANUALLY_ACTIVATED    = 'manually_activated';
    public const EVENT_START_MODE_CHANGED    = 'start_mode_changed';
}
