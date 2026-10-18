<?php

namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomInvitation;
use App\Models\User;

/**
 * Lista de invitados de las salas VIP.
 *
 * Una VIP queda cerrada para todos salvo su creador, admin y los invitados:
 *  - jugador registrado (user_id): la ve en el lobby, entra y compra con su
 *    billetera (BingoRoomCrudService::canPlayerAccessRoom);
 *  - comprador externo (teléfono): los vendedores de la red solo le venden
 *    si su teléfono está en la lista (BingoCardGenerateService::generate).
 *
 * Quién gestiona la lista lo decide BingoRoomPolicy::update (creador o admin).
 */
final class BingoRoomInvitationService
{
    public function isVip(BingoRoom $room): bool
    {
        return $room->isVip();
    }

    /** @return array<int, array<string, mixed>> */
    public function list(BingoRoom $room): array
    {
        return $room->invitations()
            ->with('user:id,username,email,first_name,last_name')
            ->latest()
            ->get()
            ->map(fn (BingoRoomInvitation $inv) => [
                'id'         => $inv->id,
                'type'       => $inv->user_id ? 'player' : 'phone',
                'name'       => $inv->user_id
                    ? (trim("{$inv->user?->first_name} {$inv->user?->last_name}") ?: ($inv->user?->username ?? '—'))
                    : ($inv->label ?: 'Comprador externo'),
                'detail'     => $inv->user_id ? ($inv->user?->username ?? $inv->user?->email) : $inv->phone,
                'created_at' => $inv->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * Invita a un jugador registrado por usuario o email.
     *
     * @throws BingoRoomException
     */
    public function addPlayer(BingoRoom $room, string $identifier, ?string $invitedBy): BingoRoomInvitation
    {
        $this->assertVip($room);

        $identifier = trim($identifier);
        $user = User::role('player')
            ->where(fn ($q) => $q->where('username', $identifier)->orWhere('email', $identifier))
            ->first(['id']);

        if (!$user) {
            throw BingoRoomException::invitedPlayerNotFound();
        }

        if ($room->invitations()->where('user_id', $user->id)->exists()) {
            throw BingoRoomException::invitationAlreadyExists();
        }

        return $room->invitations()->create([
            'user_id'            => (string) $user->id,
            'invited_by_user_id' => $invitedBy,
        ]);
    }

    /**
     * Invita a un comprador externo por teléfono (lo atiende un vendedor de la red).
     *
     * @throws BingoRoomException
     */
    public function addPhone(BingoRoom $room, string $phone, ?string $label, ?string $invitedBy): BingoRoomInvitation
    {
        $this->assertVip($room);

        $key = BingoRoomInvitation::phoneKey($phone);
        if ($key === null) {
            throw BingoRoomException::invitationInvalidPhone();
        }

        if ($room->invitations()->where('phone_key', $key)->exists()) {
            throw BingoRoomException::invitationAlreadyExists();
        }

        return $room->invitations()->create([
            'phone'              => trim($phone),
            'phone_key'          => $key,
            'label'              => $label !== null ? (trim($label) ?: null) : null,
            'invited_by_user_id' => $invitedBy,
        ]);
    }

    public function remove(BingoRoom $room, string $invitationId): void
    {
        $room->invitations()->whereKey($invitationId)->delete();
    }

    public function isUserInvited(int|string $roomId, int|string|null $userId): bool
    {
        return $userId !== null && BingoRoomInvitation::where('room_id', $roomId)->where('user_id', $userId)->exists();
    }

    public function isPhoneInvited(int|string $roomId, ?string $phone): bool
    {
        $key = BingoRoomInvitation::phoneKey($phone);

        return $key !== null && BingoRoomInvitation::where('room_id', $roomId)->where('phone_key', $key)->exists();
    }

    /** @throws BingoRoomException */
    private function assertVip(BingoRoom $room): void
    {
        if (!$this->isVip($room)) {
            throw BingoRoomException::invitationsOnlyForVip();
        }
    }
}
