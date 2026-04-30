<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Actions;

use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\DTOs\PlayerDTO;
use App\Domains\Games\Trivia\DTOs\RoomDTO;
use App\Models\Games\Trivia\TriviaRoom;
use App\Models\User;
use App\Services\WalletService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Action: Unirse a Sala.
 * 
 * Caso de uso para que un jugador se una a una sala existente.
 */
class JoinRoomAction
{
    public function __construct(
        private readonly RoomServiceInterface  $roomService,
        private readonly WalletService         $walletService,
        private readonly NotificationService   $notificationService,
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

        // Verificar si ya está registrado
        $alreadyJoined = $this->isPlayerAlreadyJoined($roomId, $playerId);

        // Si ya está registrado, solo retornar info sin hacer nada más
        if ($alreadyJoined) {
            $room = $this->roomService->getRoom($roomId);
            $players = $this->roomService->listPlayers($roomId);

            return [
                'player' => [
                    'id' => $playerId,
                    'name' => $nickname,
                    'roomId' => $roomId,
                ],
                'room' => $room,
                'players' => $players,
                'already_joined' => true,
                'message' => 'Ya estás registrado en esta sala',
            ];
        }

        // Descontar tarifa de entrada
        $this->chargeEntryFee($roomId, $playerId);

        // Unir a sala (registrar en DB y Redis)
        $playerData['nickname'] = $nickname;
        $player = $this->roomService->joinRoom($roomId, $playerId, $playerData);

        // Obtener información actualizada de la sala
        $room = $this->roomService->getRoom($roomId);
        $players = $this->roomService->listPlayers($roomId);

        // Crear notificación de inscripción
        $this->createTriviaNotification($roomId, $playerId);

        return [
            'player' => $player,
            'room' => $room,
            'players' => $players,
            'already_joined' => false,
            'message' => 'Te has unido a la sala exitosamente',
        ];
    }

    /**
     * Verifica si el jugador ya está registrado en la sala.
     */
    private function isPlayerAlreadyJoined(string $roomId, string $playerId): bool
    {
        return DB::connection('pgsql_trivia')
            ->table('trivia_participants')
            ->where('room_id', $roomId)
            ->where('user_id', $playerId)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * Crea notificación de inscripción a trivia.
     */
    private function createTriviaNotification(string $roomId, string $playerId): void
    {
        $room = TriviaRoom::find($roomId);
        if (!$room) {
            return;
        }

        $currency = null;
        if ($room->entry_currency_id) {
            $currency = DB::table('currencies')->where('id', $room->entry_currency_id)->first();
        }

        $this->notificationService->createTriviaRegistrationNotification(
            userId: $playerId,
            roomTitle: $room->title,
            roomCode: $room->code,
            entryFee: $room->entry_fee ? (float) $room->entry_fee : null,
            currencySymbol: $currency?->symbol,
        );
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

        // Descontar de la billetera usando el método existente
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
