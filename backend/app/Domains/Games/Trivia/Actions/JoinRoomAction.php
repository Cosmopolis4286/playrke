<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Actions;

use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\DTOs\PlayerDTO;
use App\Domains\Games\Trivia\DTOs\RoomDTO;
use App\Models\Games\Trivia\TriviaRoom;
use App\Models\User;
use App\Services\WalletService;
use RuntimeException;

/**
 * Action: Unirse a Sala.
 * 
 * Caso de uso para que un jugador se una a una sala existente.
 */
class JoinRoomAction
{
    public function __construct(
        private readonly RoomServiceInterface $roomService,
        private readonly WalletService        $walletService,
    ) {}

    /**
     * Ejecutar la acción.
     * 
     * @param string $roomId ID o código de la sala
     * @param string $playerId ID del jugador
     * @param string $nickname Nombre del jugador
     * @param array $playerData Datos adicionales del jugador
     */
    public function execute(
        string $roomId,
        string $playerId,
        string $nickname,
        array $playerData = []
    ): array {
        // Validar nickname
        $nickname = trim($nickname);
        if (strlen($nickname) < 2 || strlen($nickname) > 30) {
            throw new RuntimeException('El nickname debe tener entre 2 y 30 caracteres');
        }

        // Descontar tarifa de entrada si la sala tiene entry_fee
        $this->chargeEntryFee($roomId, $playerId);

        // Unir a sala
        $playerData['nickname'] = $nickname;
        $player = $this->roomService->joinRoom($roomId, $playerId, $playerData);

        // Obtener información actualizada de la sala
        $room = $this->roomService->getRoom($roomId);
        $players = $this->roomService->listPlayers($roomId);

        return [
            'player' => $player,
            'room' => $room,
            'players' => $players,
            'message' => 'Te has unido a la sala exitosamente',
        ];
    }

    /**
     * Descuenta la tarifa de entrada de la wallet del jugador.
     * Si entry_fee es 0 o null, no hace nada.
     */
    private function chargeEntryFee(string $roomId, string $playerId): void
    {
        $room = TriviaRoom::find($roomId);

        if (! $room || ! $room->entry_fee || (float) $room->entry_fee <= 0) {
            return;
        }

        $user   = User::findOrFail($playerId);
        $wallet = $user->wallets()
            ->where('currency_id', $room->entry_currency_id)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $wallet) {
            throw new RuntimeException('No tienes una billetera en la moneda requerida para esta sala.');
        }

        if (! $wallet->hasSufficientBalance((float) $room->entry_fee)) {
            throw new RuntimeException('Saldo insuficiente para unirte a esta sala. Se requieren ' . $room->entry_fee . '.');
        }

        $this->walletService->recordCardPurchase(
            walletId:    (string) $wallet->id,
            amount:      (float) $room->entry_fee,
            description: "Entrada sala Trivia: {$room->title} ({$room->code})",
        );
    }

    /**
     * Buscar sala por código.
     */
    public function findRoomByCode(string $code): RoomDTO
    {
        // Implementar búsqueda por código
        // Por ahora, asumimos que el código es suficiente para encontrar la sala
        throw new RuntimeException('Método no implementado - usar ID de sala directamente');
    }
}
