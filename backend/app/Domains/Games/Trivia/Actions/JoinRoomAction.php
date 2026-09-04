<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Actions;

use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\DTOs\PlayerDTO;
use App\Domains\Games\Trivia\DTOs\RoomDTO;
use App\Domains\Games\Trivia\Enums\TriviaPowerUpType;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Domains\Wallet\Services\WalletService;
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

        // Verificar si ya está registrado (chequeo rápido, sin lock — evita
        // pagar el costo de una transacción en el camino feliz de "ya unido").
        // La verificación que realmente importa contra condiciones de carrera
        // es la que se repite DENTRO del lock más abajo.
        if ($this->isPlayerAlreadyJoined($roomId, $playerId)) {
            return $this->alreadyJoinedResponse($roomId, $playerId, $nickname);
        }

        // Validar equipo si el modo lo requiere
        if ($teamId) {
            $this->validateTeamSelection($roomId, $teamId);
        }

        // lockForUpdate() sobre la sala serializa dos joins concurrentes del
        // mismo jugador (doble clic, reintento de red): sin el lock, ambas
        // requests podían leer isPlayerAlreadyJoined()=false, cobrar la
        // wallet dos veces, y solo una lograba insertar en
        // trivia_participants (protegido por unique(room_id,user_id)) — la
        // otra fallaba con una excepción de BD no capturada específicamente,
        // dejando al jugador cobrado de más sin inscripción ni reembolso.
        // Re-chequear isPlayerAlreadyJoined() DENTRO del lock (no solo antes)
        // es lo que realmente cierra la ventana de carrera.
        $alreadyJoinedInRace = false;

        DB::connection('pgsql_trivia')->transaction(function () use ($roomId, $playerId, $nickname, $teamId, &$alreadyJoinedInRace) {
            DB::connection('pgsql_trivia')->table('trivia_rooms')->where('id', $roomId)->lockForUpdate()->first();

            if ($this->isPlayerAlreadyJoined($roomId, $playerId)) {
                $alreadyJoinedInRace = true;
                return;
            }

            // Descontar tarifa de entrada — devuelve lo realmente cobrado
            // (monto + moneda), que puede diferir de entry_fee/entry_currency_id
            // (legado) cuando la sala usa fee_by_currency o conversión.
            [$chargeAmount, $chargeCurrencyId] = $this->chargeEntryFee($roomId, $playerId);

            // Insertar en trivia_participants con el monto/moneda REALES
            // cobrados — antes se persistía siempre $room->entry_fee, que
            // podía no coincidir con lo cobrado (multidivisa/conversión),
            // desincronizando el monto a reembolsar en caso de cancelación.
            $this->registerParticipantInDatabase($roomId, $playerId, $nickname, $teamId, $chargeAmount, $chargeCurrencyId);
        });

        if ($alreadyJoinedInRace) {
            return $this->alreadyJoinedResponse($roomId, $playerId, $nickname);
        }

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
     * Respuesta estándar cuando el jugador ya está inscrito — usada tanto en
     * el chequeo rápido inicial como cuando la re-verificación dentro del
     * lock detecta que otra request concurrente ya lo inscribió primero.
     */
    private function alreadyJoinedResponse(string $roomId, string $playerId, string $nickname): array
    {
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
     * Si no hay tarifa configurada, no cobra nada y devuelve [0.0, null].
     *
     * @return array{0: float, 1: ?string} Monto y moneda REALMENTE cobrados
     *         — deben persistirse tal cual en trivia_participants.paid_amount/
     *         paid_currency_id (antes se persistía siempre $room->entry_fee,
     *         que no coincide con lo cobrado cuando la sala usa
     *         fee_by_currency o conversión, desincronizando el monto a
     *         reembolsar en caso de cancelación).
     */
    private function chargeEntryFee(string $roomId, string $playerId): array
    {
        $room = TriviaRoom::find($roomId);

        if (! $room) {
            return [0.0, null];
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
            // Sala sin moneda específica configurada: si tiene un costo base
            // (entry_fee / entry_fee_usd), se cobra aplicando conversión de
            // tasa de cambio sobre la wallet activa del jugador, igual que Bingo.
            $baseFee = (float) ($room->entry_fee_usd ?: $room->entry_fee ?? 0);

            if ($baseFee > 0) {
                return $this->chargeEntryFeeWithConversion($room, $playerId, $baseFee);
            }

            return [0.0, null];
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

        if (! $chargeWallet->hasSufficientPlayableBalance($chargeAmount)) {
            $symbol = $chargeWallet->currency?->symbol ?? $chargeCurrencyId;
            throw new RuntimeException("Saldo insuficiente. Se requieren {$symbol} {$chargeAmount}.");
        }

        $this->walletService->recordTriviaEntry(
            wallet:      $chargeWallet,
            amount:      $chargeAmount,
            description: "Entrada sala Trivia: {$room->title} ({$room->code})",
            roomId:      (string) $room->id,
            roomName:    $room->title,
        );

        return [$chargeAmount, $chargeCurrencyId];
    }

    /**
     * Cobra la entrada cuando la sala no define una moneda específica:
     * convierte el costo base (USD) a la moneda de la wallet activa del
     * jugador usando la tasa de cambio global, igual que en Bingo.
     *
     * @return array{0: float, 1: ?string} Monto y moneda realmente cobrados.
     */
    private function chargeEntryFeeWithConversion(TriviaRoom $room, string $playerId, float $baseFeeUsd): array
    {
        $user   = User::findOrFail($playerId);
        $wallet = $user->wallets()
            ->where('status', 'ACTIVE')
            ->with('currency')
            ->orderByDesc('balance')
            ->first();

        if (! $wallet) {
            throw new RuntimeException('No tienes una billetera activa para pagar la entrada de esta sala.');
        }

        $exchangeRate = $this->getPlayerExchangeRate((string) $wallet->currency_id);
        $chargeAmount = round($baseFeeUsd * $exchangeRate, 2);

        if (! $wallet->hasSufficientPlayableBalance($chargeAmount)) {
            $symbol = $wallet->currency?->symbol ?? '';
            throw new RuntimeException("Saldo insuficiente. Se requieren {$symbol} {$chargeAmount}.");
        }

        $this->walletService->recordTriviaEntry(
            wallet:      $wallet,
            amount:      $chargeAmount,
            description: "Entrada sala Trivia: {$room->title} ({$room->code})",
            roomId:      (string) $room->id,
            roomName:    $room->title,
        );

        return [$chargeAmount, (string) $wallet->currency_id];
    }

    /**
     * Tasa de cambio activa más reciente para una moneda (USD → moneda local).
     * Si no existe tasa registrada, se asume 1:1 (la moneda es la base).
     */
    private function getPlayerExchangeRate(string $currencyId): float
    {
        return (float) ExchangeRate::where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('created_at')
            ->value('amount')
            ?: 1.0;
    }

    /**
     * Registra al participante en la tabla trivia_participants.
     *
     * @param float $paidAmount Monto REALMENTE cobrado (ver chargeEntryFee) —
     *        no $room->entry_fee, que puede no coincidir cuando la sala usa
     *        fee_by_currency o conversión.
     * @param string|null $paidCurrencyId Moneda REALMENTE cobrada.
     */
    private function registerParticipantInDatabase(
        string $roomId,
        string $playerId,
        string $nickname,
        ?string $teamId,
        float $paidAmount,
        ?string $paidCurrencyId,
    ): void {
        $room = TriviaRoom::find($roomId);
        $allowPowerups = (bool) (($room?->settings ?? [])['allow_powerups'] ?? false);

        DB::connection('pgsql_trivia')->table('trivia_participants')->insert([
            'id' => (string) Str::ulid(),
            'room_id' => $roomId,
            'team_id' => $teamId,
            'user_id' => $playerId,
            'status' => 'confirmed',
            'registered_at' => now(),
            'confirmed_at' => now(),
            'paid_amount' => $paidAmount,
            'paid_currency_id' => $paidCurrencyId,
            // 1 uso de cada tipo por partida — solo si la sala los habilita
            // (settings.allow_powerups, toggle ya existente en RoomsCreate.vue).
            'power_ups' => $allowPowerups
                ? json_encode(TriviaPowerUpType::defaultAllowance())
                : null,
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
