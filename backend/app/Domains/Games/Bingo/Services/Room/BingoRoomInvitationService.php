<?php

namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomInvitation;
use App\Models\User;
use App\Models\UserLevel;

/**
 * Lista de invitados de las salas VIP.
 *
 * Una VIP queda cerrada para todos salvo su creador, admin y los invitados:
 *  - jugador registrado (user_id): la ve en el lobby, entra y compra con su
 *    billetera (BingoRoomCrudService::canPlayerAccessRoom);
 *  - comprador externo (teléfono): los vendedores de la red solo le venden
 *    si su teléfono está en la lista (BingoCardGenerateService::generate).
 *
 * Además, el creador puede fijar requisitos de acceso automático (nivel
 * mínimo y/o gasto mínimo en los últimos SPEND_WINDOW_DAYS días): un jugador
 * registrado que cumpla TODOS los fijados entra sin invitación. Los
 * compradores externos siempre necesitan invitación (no tienen nivel ni gasto).
 *
 * Quién gestiona la lista lo decide BingoRoomPolicy::update (creador o admin).
 */
final class BingoRoomInvitationService
{
    /** Ventana del requisito de gasto mínimo. */
    public const SPEND_WINDOW_DAYS = 30;

    /** @var array<string, array{level: int, spend_usd: float}> perfil por jugador, memo por request */
    private array $profiles = [];

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

    // -------------------------------------------------------------------------
    // Requisitos de acceso automático
    // -------------------------------------------------------------------------

    /** @return array{min_level: ?int, min_spend_usd: ?float, spend_window_days: int} */
    public function requirements(BingoRoom $room): array
    {
        $room->loadMissing('config');

        return [
            'min_level'         => $room->config?->vip_min_level !== null ? (int) $room->config->vip_min_level : null,
            'min_spend_usd'     => $room->config?->vip_min_spend_usd !== null ? (float) $room->config->vip_min_spend_usd : null,
            'spend_window_days' => self::SPEND_WINDOW_DAYS,
        ];
    }

    /**
     * Fija (o quita, con null) los requisitos de acceso automático.
     *
     * @throws BingoRoomException
     */
    public function updateRequirements(BingoRoom $room, ?int $minLevel, ?float $minSpendUsd): array
    {
        $this->assertVip($room);
        $room->loadMissing('config');

        $room->config?->update([
            'vip_min_level'     => $minLevel !== null && $minLevel > 1 ? $minLevel : null,
            'vip_min_spend_usd' => $minSpendUsd !== null && $minSpendUsd > 0 ? round($minSpendUsd, 2) : null,
        ]);

        return $this->requirements($room->fresh('config'));
    }

    /**
     * true si la sala tiene al menos un requisito fijado y el jugador cumple
     * todos. Sin requisitos → false (solo entra por invitación).
     */
    public function meetsRequirements(BingoRoom $room, int|string|null $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        $req = $this->requirements($room);
        if ($req['min_level'] === null && $req['min_spend_usd'] === null) {
            return false;
        }

        $profile = $this->playerProfile($userId);

        return ($req['min_level'] === null || $profile['level'] >= $req['min_level'])
            && ($req['min_spend_usd'] === null || $profile['spend_usd'] >= $req['min_spend_usd']);
    }

    /**
     * Nivel del jugador y lo que gastó comprando cartones con su billetera en
     * los últimos SPEND_WINDOW_DAYS días, en USD a la tasa de cada compra.
     * Las compras propias del jugador son lotes con user_id = jugador y sin
     * vendedor; los lotes reembolsados no cuentan.
     *
     * @return array{level: int, spend_usd: float}
     */
    public function playerProfile(int|string $userId): array
    {
        $key = (string) $userId;

        return $this->profiles[$key] ??= [
            'level'     => (int) (UserLevel::where('user_id', $userId)->value('current_level') ?? 1),
            'spend_usd' => round((float) BingoCardLot::query()
                ->where('user_id', $userId)
                ->whereNull('seller_user_id')
                ->where('status', 'ACTIVE')
                ->where('created_at', '>=', now()->subDays(self::SPEND_WINDOW_DAYS))
                ->selectRaw('COALESCE(SUM(sale_amount / NULLIF(exchange_rate, 0)), 0) AS usd')
                ->toBase()
                ->value('usd'), 2),
        ];
    }

    /** @throws BingoRoomException */
    private function assertVip(BingoRoom $room): void
    {
        if (!$this->isVip($room)) {
            throw BingoRoomException::invitationsOnlyForVip();
        }
    }
}
