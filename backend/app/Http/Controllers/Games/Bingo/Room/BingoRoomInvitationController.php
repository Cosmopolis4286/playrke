<?php

namespace App\Http\Controllers\Games\Bingo\Room;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\BingoRoomInvitationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Lista de invitados de una sala VIP. La gestiona quien puede editar la sala
 * (BingoRoomPolicy::update: su creador con games.bingo.manage, o admin).
 */
final class BingoRoomInvitationController extends Controller
{
    public function __construct(
        private readonly BingoRoomInvitationService $service,
    ) {
    }

    public function destroy(BingoRoom $room, string $invitation) : JsonResponse
    {
        $this->authorize('update', $room);

        return $this->handleServiceJson(function() use ($room, $invitation) {
            $this->service->remove($room, $invitation);

            return ['invitations' => $this->service->list($room)];
        });
    }

    public function index(BingoRoom $room) : JsonResponse
    {
        $this->authorize('update', $room);

        return $this->handleServiceJson(fn () => [
            'is_vip' => $this->service->isVip($room),
            'invitations' => $this->service->list($room),
            'requirements' => $this->service->requirements($room),
        ]);
    }

    public function store(Request $request, BingoRoom $room) : JsonResponse
    {
        $this->authorize('update', $room);

        $data = $request->validate([
            'type' => ['required', Rule::in(['player', 'phone'])],
            'identifier' => ['required_if:type,player', 'nullable', 'string', 'max:150'],
            'phone' => ['required_if:type,phone', 'nullable', 'string', 'max:30'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        return $this->handleServiceJson(function() use ($room, $data) {
            $invitedBy = Auth::id() !== null ? (string) Auth::id() : null;

            $data['type'] === 'player'
                ? $this->service->addPlayer($room, (string) $data['identifier'], $invitedBy)
                : $this->service->addPhone($room, (string) $data['phone'], $data['label'] ?? null, $invitedBy);

            return ['invitations' => $this->service->list($room)];
        });
    }

    /** Requisitos de acceso automático (null = sin requisito). */
    public function updateRequirements(Request $request, BingoRoom $room) : JsonResponse
    {
        $this->authorize('update', $room);

        $data = $request->validate([
            'min_level' => ['nullable', 'integer', 'min:1', 'max:999'],
            'min_spend_usd' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        return $this->handleServiceJson(fn () => [
            'requirements' => $this->service->updateRequirements(
                $room,
                isset($data['min_level']) ? (int) $data['min_level'] : null,
                isset($data['min_spend_usd']) ? (float) $data['min_spend_usd'] : null,
            ),
        ]);
    }
}
