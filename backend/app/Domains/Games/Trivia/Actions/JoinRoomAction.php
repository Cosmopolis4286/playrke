<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Actions;

use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\DTOs\PlayerDTO;
use App\Domains\Games\Trivia\DTOs\RoomDTO;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\User;
use App\Services\WalletService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
     * @param string|null $teamId ID del equipo (para modos teams/duel)
     */
    public function execute(
        string $roomId,
        string $playerId,
        string $nickname,
        array $playerData = [],
        ?string $teamId = null
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

        // Validar equipo si el modo lo requiere
        if ($teamId) {
            $this->validateTeamSelection($roomId, $teamId);
        }

        // Descontar tarifa de entrada
        $this->chargeEntryFee($roomId, $playerId);

        // Insertar en trivia_participants (PostgreSQL)
        $this->registerParticipantInDatabase($roomId, $playerId, $nickname, $teamId);

        // Unir a sala en Redis (tiempo real)
        $playerData['nickname'] = $nickname;
        $playerData['team_id']  = $teamId;
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
            'team_id' => $teamId,
        ];
    }

    /**
     * Validar que el equipo existe y tiene espacio.
     */
    private function validateTeamSelection(string $roomId, string $teamId): void
    {
        $team = DB::connection('pgsql_trivia')
            ->table('trivia_teams')
            ->where('id', $teamId)
            ->where('room_id', $roomId)
            ->first();

        if (!$team) {
            throw new RuntimeException('El equipo seleccionado no existe en esta sala');
        }

        if ($team->current_players >= $team->max_players) {
            throw new RuntimeException('El equipo seleccionado está lleno');
        }
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
     * Soporta fee_by_currency (multidivisa) y entry_fee/entry_currency_id (legado).
     * Si no hay tarifa configurada, no hace nada.
     */
    private function chargeEntryFee(string $roomId, string $playerId): void
    {
        $room = TriviaRoom::find($roomId);

        if (! $room) {
            return;
        }

        // ── Resolver monedas disponibles ────────────────────────────────────
        $feeByCurrency = $room->fee_by_currency; // array|null (cast en modelo)

        // Construir mapa currencyId => amount
        $feeMap = [];
        if (! empty($feeByCurrency) && is_array($feeByCurrency)) {
            foreach ($feeByCurrency as $cid => $amount) {
                if ((float) $amount > 0) {
                    $feeMap[(string) $cid] = (float) $amount;
                }
            }
        } elseif ($room->entry_currency_id && (float) ($room->entry_fee ?? 0) > 0) {
            $feeMap[(string) $room->entry_currency_id] = (float) $room->entry_fee;
        }

        if (empty($feeMap)) {
            return; // Sala gratuita
        }

        // ── Buscar la primera wallet del usuario que coincida con una moneda aceptada
        $user    = User::findOrFail($playerId);
        $wallets = $user->wallets()
            ->whereIn('currency_id', array_keys($feeMap))
            ->where('status', 'ACTIVE')
            ->with('currency')
            ->get()
            ->keyBy(fn($w) => (string) $w->currency_id);

        $chargeWallet     = null;
        $chargeAmount     = 0.0;
        $chargeCurrencyId = null;

        foreach ($feeMap as $cid => $amount) {
            if (isset($wallets[$cid])) {
                $chargeWallet     = $wallets[$cid];
                $chargeAmount     = $amount;
                $chargeCurrencyId = $cid;
                break;
            }
        }

        if (! $chargeWallet) {
            $symbols = collect($feeMap)->keys()
                ->map(fn($cid) => DB::table('currencies')->where('id', $cid)->value('symbol') ?? $cid)
                ->implode(', ');
            throw new RuntimeException("No tienes una billetera en la moneda requerida para esta sala ({$symbols}).");
        }

        if (! $chargeWallet->hasSufficientBalance($chargeAmount)) {
            $symbol = $chargeWallet->currency?->symbol ?? $chargeCurrencyId;
            throw new RuntimeException("Saldo insuficiente. Se requieren {$symbol} {$chargeAmount}.");
        }

        $this->walletService->recordTriviaEntry(
            wallet:      $chargeWallet,
            amount:      $chargeAmount,
            description: "Entrada sala Trivia: {$room->title} ({$room->code})",
        );
    }

    /**
     * Registra al participante en la tabla trivia_participants.
     */
    private function registerParticipantInDatabase(string $roomId, string $playerId, string $nickname, ?string $teamId = null): void
    {
        $room = TriviaRoom::find($roomId);
        if (!$room) {
            return;
        }

        DB::connection('pgsql_trivia')->table('trivia_participants')->insert([
            'id' => (string) Str::ulid(),
            'room_id' => $roomId,
            'team_id' => $teamId,
            'user_id' => $playerId,
            'status' => 'confirmed',
            'registered_at' => now(),
            'confirmed_at' => now(),
            'paid_amount' => $room->entry_fee ?? 0,
            'paid_currency_id' => $room->entry_currency_id,
            'metadata' => json_encode(['nickname' => $nickname]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Incrementar contador de jugadores en el equipo
        if ($teamId) {
            DB::connection('pgsql_trivia')
                ->table('trivia_teams')
                ->where('id', $teamId)
                ->increment('current_players');
        }
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
