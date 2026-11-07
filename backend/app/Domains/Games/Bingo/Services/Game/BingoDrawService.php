<?php

namespace App\Domains\Games\Bingo\Services\Game;

use App\Domains\Games\Bingo\Events\BingoGameEnded;
use App\Domains\Games\Bingo\Events\BingoGameStarted;
use App\Domains\Games\Bingo\Events\BingoNumberCalled;
use App\Domains\Games\Bingo\Events\BingoWinnerDeclared;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Draw\BingoDrawCode;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Domains\Games\Bingo\Services\Room\BingoRoomStatsAggregator;
use App\Domains\Games\Bingo\Services\Winner\BingoPrizePayoutService;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Domains\Platform\WinEvent\Services\WinEventService;
use App\Exceptions\Domain\ExchangeRate\ExchangeRateException;
use App\Models\GameParticipation;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\Roles\Player\External\ExternalPlayerParticipation;
use App\Models\User;
use App\Services\ExchangeRate\CurrencyExchangeRateService;
use App\Services\Games\GameCompletionService;
use App\Services\User\UserLevelService;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class BingoDrawService
{
    /** Bolas restantes por debajo de las cuales una victoria cuenta como "lucky_winner" (sorteo casi agotado). */
    public const LUCKY_WINNER_REMAINING_THRESHOLD = 3;

    /** Número de fichas entre cada verificación de compras mid-sorteo. */
    private const ACTIVE_CARDS_REFRESH_INTERVAL = 5;
    private const DRAW_LOCK_MIN_TTL_SECONDS = 5400;
    private const DRAW_LOCK_SAFETY_MARGIN_SECONDS = 1800;
    // En local se usa BINGO_DEV_INTERVAL_SECONDS si no hay time_chip en la config.
    private const CHIP_INTERVAL_DEV = 3;
    private const CHIP_INTERVAL_SECONDS = 5; // fallback si time_chip no está configurado

    /**
     * Cartones elegibles de la sala, cacheados en memoria durante la corrida del sorteo.
     * Keyed por json_encode($with) para evitar que broadcastPlayersProgress
     * (que carga ['roomUser.user']) envene el cache de detectWinnerAndEnd
     * (que necesita ['roomUser.user','cardLot']).
     *
     * Se recarga si: (a) no existe para esta sala/with, (b) es de otra sala,
     * (c) fue invalidado explícitamente, o (d) cada ACTIVE_CARDS_REFRESH_INTERVAL chips.
     *
     * @var array<string, \Illuminate\Support\Collection<int, BingoCard>>
     */
    private array $activeCardsCache = [];
    private ?string $activeCardsCacheRoomId = null;

    /**
     * Índice de la ficha actual dentro del sorteo (0-based).
     * El draw loop lo actualiza una vez por chip antes de llamar a broadcastPlayersProgress
     * y detectWinnerAndEnd, de modo que el intervalo periódico de activeCards() sea
     * "cada N chips reales", no "cada N llamadas al método".
     */
    private int $currentChipIndex = 0;

    /** Intervalo entre fichas (segundos) de la corrida activa — usado para calcular el TTL del cache de draw-state. */
    private int $currentInterval = 5;

    /** Cache de tasas de cambio activas resuelta por moneda durante la corrida actual. */
    private array $rateCache = [];

    public function __construct(
        private readonly BingoPrizePayoutService $prizePayoutService,
        private readonly WinEventService $winEventService,
        private readonly BingoRoomAuditLogger $auditLogger,
        private readonly BingoRoomStatsAggregator $statsAggregator,
        private readonly CurrencyExchangeRateService $currencyExchangeRate,
        private readonly GameCompletionService $gameCompletionService,
        private readonly UserLevelService $userLevelService,
        private readonly BingoPatternPositionResolver $patternResolver = new BingoPatternPositionResolver,
    ) {
    }

    /**
     * Punto único de persistencia para un cartón ganador: bajo lock + transacción,
     * vuelve a verificar que el cartón siga ACTIVE (evita doble marcado si el sorteo
     * automático y una verificación manual del jugador caen al mismo tiempo sobre el
     * mismo cartón), lo marca como WINNER, registra el premio y emite los broadcasts.
     *
     * Devuelve null si el cartón ya no estaba ACTIVE (otro proceso lo reclamó primero).
     */
    public function claimCardWin(
        BingoRoom $room,
        BingoRound $round,
        BingoPattern $pattern,
        string $cardId,
        int $lastDrawId,
        bool $isLuckyWinner = false,
    ) : ?array {
        $roundId = (string) $round->id;
        $cardPrize = null;

        // Pre-cargar prizes FUERA de la transacción para no hacer un SELECT adicional
        // mientras se sostiene un lockForUpdate sobre bingo_cards — reduce la ventana
        // de lock y elimina el riesgo de deadlock si otro proceso edita bingo_round_prizes
        // al mismo tiempo (los dos locks crearían un ciclo InnoDB).
        if (! $round->relationLoaded('prizes')) {
            $round->load('prizes');
        }

        $isDemoRoom = $room->isDemo();

        /** @var array{winnerData: array<string,mixed>, broadcast: array<string,mixed>}|null $txResult */
        $txResult = DB::transaction(function() use ($room, $round, $roundId, $pattern, $cardId, $lastDrawId, $isDemoRoom, &$cardPrize) {
            // Mantener el mismo orden de locks que BingoCardPaymentReviewService::reject():
            // primero pagos y después cartón. Así un rechazo concurrente no puede
            // confirmar un pago inválido mientras este proceso registra un ganador.
            $cardLotId = BingoCard::where('id', $cardId)
                ->where('room_id', (string) $room->id)
                ->value('card_lot_id');

            if (! $cardLotId) {
                return null;
            }

            // Los pagos soft-deleted también cuentan: borrar lógicamente un
            // comprobante PENDING/REJECTED no puede convertirlo en una venta válida.
            $payments = BingoCardPayment::withTrashed()
                ->where('card_lot_id', (string) $cardLotId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $cardLot = BingoCardLot::where('id', (string) $cardLotId)
                ->lockForUpdate()
                ->first();

            $card = BingoCard::where('id', $cardId)
                ->where('room_id', (string) $room->id)
                ->lockForUpdate()
                ->with(['roomUser.user', 'cardLot'])
                ->first();

            if (! $card) {
                return null;
            }

            // Última barrera de seguridad: no confiar solo en el filtro del caller
            // ni en el cache del sorteo. Revalidar lote y pago bajo los locks tomados.
            if (
                ! $cardLot
                || $cardLot->status !== 'ACTIVE'
                || $payments->contains(fn ($payment) => $payment->payment_status !== 'APPROVED')
            ) {
                Log::warning('[BingoDrawService] Reclamo de cartón no elegible bloqueado', [
                    'card_id' => (string) $card->id,
                    'card_lot_id' => (string) $cardLotId,
                    'lot_status' => $cardLot?->status,
                    'payment_statuses' => $payments->pluck('payment_status')->all(),
                ]);

                return null;
            }

            $externalPlayerIds = $payments->pluck('external_player_id')->filter()->map(fn ($id) => (string) $id)->unique()->values();
            // La participación conserva la titularidad cuando pagos históricos
            // no guardaron external_player_id.
            $participationIds = ExternalPlayerParticipation::withTrashed()
                ->where('participable_type', BingoCard::class)
                ->whereRaw('JSON_UNQUOTE(participable_id) = ?', [(string) $card->id])
                ->pluck('external_player_id')
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values();
            $externalPlayerIds = $externalPlayerIds->merge($participationIds)->unique()->values();

            if ($externalPlayerIds->count() > 1) {
                Log::error('[BingoDrawService] Cartón asociado a múltiples compradores externos; reclamo bloqueado', [
                    'card_id' => (string) $card->id,
                    'external_player_ids' => $externalPlayerIds->all(),
                ]);
                return null;
            }

            $hasExternalPlayer = $externalPlayerIds->isNotEmpty();
            $externalPlayerId = $hasExternalPlayer ? (string) $externalPlayerIds->first() : null;
            $externalPlayer = $externalPlayerId
                ? \App\Models\Roles\Player\External\ExternalPlayer::withTrashed()->find($externalPlayerId)
                : null;

            // Un room_user compartido identifica al vendedor, no al comprador.
            // No publicar ni registrar al vendedor como ganador económico.
            $userId = $hasExternalPlayer ? null : ($card->roomUser?->user_id ?? $card->creator_user_id);
            $user = $hasExternalPlayer ? null : ($card->roomUser?->user ?? User::find($userId));
            // El nombre real del comprador externo no se difunde en la sala.
            $userName = $hasExternalPlayer
                ? 'Comprador externo'
                : ($user?->username ?? ('Jugador #' . $userId));

            // Si el cartón ya es WINNER, el premio fue creado en un intento anterior
            // (el job de sorteo murió después del commit de bingo_card_prizes pero
            // antes de completar el payout). Recuperar el premio existente y
            // continuar con el pago en lugar de intentar crear un duplicado
            // (lo que lanzaría Duplicate entry sobre bingo_card_prizes_card_round_unique).
            if ((int) $card->status === BingoCard::STATUS_WINNER) {
                $existing = BingoCardPrize::where('winning_card_id', $cardId)
                    ->where('round_id', $roundId)
                    ->lockForUpdate()
                    ->first();

                if (! $existing) {
                    // Cartón marcado WINNER pero sin fila de premio — estado corrupto,
                    // no hay nada que pagar. Loguear para investigación manual.
                    Log::error('[BingoDrawService] Cartón WINNER sin BingoCardPrize — estado inconsistente', [
                        'card_id' => $cardId,
                        'round_id' => $roundId,
                    ]);

                    return null;
                }

                // Completar titularidad y retener premios externos históricos.
                if ($externalPlayerId !== null) {
                    if (! $existing->external_player_id) {
                        $existing->external_player_id = $externalPlayerId;
                    }
                    if ($existing->hasCompletedPayment() || $existing->isClaimed()) {
                        $existing->markAsDisputed();
                    } elseif ($existing->isWaiting() || $existing->isProcessing()) {
                        $existing->prize_status = 'suspended';
                    }
                    $existing->save();
                }

                // Solo reintentar premios internos no disputados.
                if ($existing->isClaimed() || $existing->isCancelled() || $existing->isDisputed()) {
                    return null;
                }

                $cardPrize = $existing;
                $prize = (float) $existing->usd_amount;

                Log::warning('[BingoDrawService] Cartón ya reclamado — retomando pago del premio existente', [
                    'card_prize_id' => $cardPrize->id,
                    'card_id' => $cardId,
                    'round_id' => $roundId,
                    'prize_status' => $existing->prize_status,
                ]);

                return [
                    'winnerData' => [
                        'user_id' => $userId !== null ? (int) $userId : null,
                        'external_player_id' => $externalPlayerId,
                        'user_name' => $userName,
                        'card_id' => (string) $card->id,
                        'card_serial' => $card->serial_number,
                        'card_prize_id' => (string) $cardPrize->id,
                        'prize' => $prize,
                        'pattern' => $pattern->name,
                        'round' => $round->round,
                    ],
                    'broadcast' => [
                        'roomId' => (int) $room->id,
                        'drawId' => $lastDrawId,
                        'winnerId' => $userId !== null ? (int) $userId : 0,
                        'winnerName' => $userName,
                        'cardId' => (int) $card->id,
                        'pattern' => $pattern->name,
                        'prize' => $prize,
                    ],
                    'wasNewlyCreated' => false,
                ];
            }

            if ((int) $card->status !== BingoCard::STATUS_ACTIVE) {
                // Otro proceso lo reclamó en un estado distinto (cancelled, etc.)
                return null;
            }

            $card->update(['status' => BingoCard::STATUS_WINNER]);
            $this->invalidateActiveCardsCache();

            $prizeAmounts = $this->resolvePrizeAmounts($room, $round, $card);
            $prize = $prizeAmounts['usd_amount'];

            // prize_type viene del enum canónico de la ronda (PCT/FIXED/OTHER),
            // no del nombre del patrón (string mutable que puede renombrarse).
            $prizeType = match ($round->prize_type) {
                'FIXED' => 'full_house',
                'PCT' => 'serial',
                'OTHER' => 'serial',
                default => 'serial',
            };

            // La identidad económica de una compra externa vive en el pago del
            // lote, no en room_user_id (compartido con el vendedor). Si aparecen
            // varios compradores para el mismo lote, se conserva el premio en
            // revisión sin seleccionar un beneficiario arbitrariamente.
            $cardPrize = BingoCardPrize::create([
                'room_id' => (string) $room->id,
                'creator_user_id' => (string) ($userId ?? $card->creator_user_id),
                'seller_user_id' => $card->seller_user_id ? (string) $card->seller_user_id : null,
                'seller_type' => $card->seller_type,
                'external_player_id' => $externalPlayerId,
                'room_user_id' => (string) $card->room_user_id,
                'card_lot_id' => (string) $card->card_lot_id,
                'winning_card_id' => (string) $card->id,
                'currency_id' => (string) $card->currency_id,
                'round_id' => $roundId,
                'status' => 'ACTIVE',
                'prize_type' => $prizeType,
                'winning_pattern' => $pattern->name,
                'usd_amount' => $prizeAmounts['usd_amount'],
                'local_amount' => $prizeAmounts['local_amount'],
                // Sala DEMO (sin dinero real): el premio queda registrado pero
                // cancelado de entrada — ni el pago de abajo ni el comando de
                // reintentos (waiting/processing) lo acreditan nunca.
                'prize_status' => $isDemoRoom ? 'cancelled' : ($hasExternalPlayer ? 'suspended' : 'waiting'),
                'verification_code' => strtoupper(bin2hex(random_bytes(8))),
            ]);

            Log::info('[BingoDrawService] Premio registrado en bingo_card_prizes', [
                'card_prize_id' => $cardPrize->id,
                'card_id' => $card->id,
                'round_id' => $roundId,
                'round_number' => $round->round,
                'pattern' => $pattern->name,
                'prize_type' => $prizeType,
                'user_id' => $userId,
            ]);

            return [
                'winnerData' => [
                    'user_id' => $userId !== null ? (int) $userId : null,
                    'external_player_id' => $externalPlayerId,
                    'user_name' => $userName,
                    'card_id' => (string) $card->id,
                    'card_serial' => $card->serial_number,
                    'card_prize_id' => (string) $cardPrize->id,
                    'prize' => $prize,
                    'pattern' => $pattern->name,
                    'round' => $round->round,
                ],
                'wasNewlyCreated' => true,
                // Payload para broadcast — capturado aquí mientras los IDs están en scope.
                'broadcast' => [
                    'roomId' => (int) $room->id,
                    'drawId' => $lastDrawId,
                    'winnerId' => $userId !== null ? (int) $userId : 0,
                    'winnerName' => $userName,
                    'cardId' => (int) $card->id,
                    'pattern' => $pattern->name,
                    'prize' => $prize,
                ],
            ];
        });

        // Broadcast y pago FUERA de la transacción: la fila bingo_card_prizes ya está commitada.
        if ($txResult !== null && $cardPrize !== null) {
            $winnerData = $txResult['winnerData'];
            $broadcastWinner = $txResult['broadcast'];

            if ($txResult['wasNewlyCreated']) {
                $this->auditLogger->log($room, BingoRoomAuditLogger::EVENT_PRIZE_CREATED, 'system', 'Premio creado para cartón ganador', [
                    'card_prize_id' => (string) $cardPrize->id,
                    'winning_card_id' => $winnerData['card_id'],
                    'user_id' => $winnerData['user_id'] !== null ? (string) $winnerData['user_id'] : null,
                    'external_player_id' => $winnerData['external_player_id'] ?? null,
                    'round_id' => (string) $round->id,
                    'usd_amount' => $cardPrize->usd_amount,
                    'pattern' => $winnerData['pattern'],
                ]);
            }

            broadcast(new BingoWinnerDeclared(
                roomId: $broadcastWinner['roomId'],
                drawId: $broadcastWinner['drawId'],
                winnerId: $broadcastWinner['winnerId'],
                winnerName: $broadcastWinner['winnerName'],
                cardId: $broadcastWinner['cardId'],
                pattern: $broadcastWinner['pattern'],
                prize: $broadcastWinner['prize'],
            ));

            if ($isDemoRoom) {
                // Sala DEMO: ganador simulado. Sin ticker público de ganadores,
                // sin acreditación a la billetera y sin XP/logros.
                if ($txResult['wasNewlyCreated']) {
                    $this->auditLogger->log($room, BingoRoomAuditLogger::EVENT_PRIZE_CREATED, 'system', 'Sala DEMO: premio simulado, no se acredita', [
                        'card_prize_id' => (string) $cardPrize->id,
                        'usd_amount' => $cardPrize->usd_amount,
                    ]);
                }

                return $winnerData;
            }

            $cardPrize->loadMissing('currency');

            // Los compradores externos no tienen una cuenta pública de usuario:
            // no publicar ganancias atribuibles al vendedor ni exponer datos personales.
            if ($winnerData['user_id'] !== null) {
                try {
                    $this->winEventService->reportMoney(
                        userId: (string) $winnerData['user_id'],
                        winnerNameOverride: null,
                        gameSlug: 'bingo',
                        gameName: 'Bingo',
                        amountUsd: $broadcastWinner['prize'],
                        amountLocal: (float) $cardPrize->local_amount,
                        currencyId: $cardPrize->currency_id,
                        sourceReference: (string) $cardPrize->id,
                        metadata: [
                            'room_id' => (string) $cardPrize->room_id,
                            'pattern' => $broadcastWinner['pattern'],
                        ],
                    );
                } catch (Throwable $e) {
                    Log::error('[BingoDrawService] Error al registrar win event — payout continúa', [
                        'card_prize_id' => $cardPrize->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->prizePayoutService->payout($cardPrize);

            // XP y logros del ganador — falla contenida, no debe afectar el payout ya realizado.
            try {
                $winnerUser = User::find((string) $broadcastWinner['winnerId']);
                if ($winnerUser) {
                    $this->gameCompletionService->reportParticipation(
                        user: $winnerUser,
                        game: GameParticipation::GAME_BINGO,
                        won: true,
                        xp: $this->userLevelService->calculateXpReward('game_won'),
                        roomId: (string) $broadcastWinner['roomId'],
                        customContext: ['lucky_winner_last_call' => $isLuckyWinner],
                    );
                }
            } catch (Throwable $e) {
                Log::warning('[BingoDrawService] Error al otorgar XP/logros al ganador', [
                    'card_prize_id' => $cardPrize->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            $winnerData = null;
        }

        return $winnerData;
    }

    /**
     * Calcula el progreso de cada jugador (fichas faltantes para el patrón activo).
     * Público para que otros servicios (ej. BingoGameService::getDrawState) puedan
     * incluirlo en la respuesta de resync sin duplicar la lógica.
     *
     * @return array<int, array{user_id: int, user_name: string, missing: int}>
     */
    public function computePlayersProgress(BingoRoom $room, array $patternPositions, array $drawnNumbers) : array
    {
        if (empty($patternPositions)) {
            return [];
        }

        $variant = $room->config?->game_variant ?? BingoVariant::BINGO_75;
        $hasFreeCell = BingoVariant::hasFreeCell($variant);
        $drawnSet = array_flip($drawnNumbers);
        $cards = $this->activeCards($room, ['roomUser.user']);
        $playerBest = [];

        foreach ($cards as $card) {
            $matrix = $card->card_numbers ?? [];
            $missing = 0;

            foreach ($patternPositions as $cell) {
                $num = $matrix[$cell['col']][$cell['row']] ?? null;
                if ($cell['free'] || ($hasFreeCell && $num === null)) {
                    continue;
                }
                if ($num === null || ! isset($drawnSet[$num])) {
                    $missing++;
                }
            }

            $userId = $card->roomUser?->user_id ?? $card->creator_user_id;
            if (! $userId) {
                continue;
            }

            $userId = (int) $userId;
            $rawName = $card->roomUser?->user?->name ?? '';
            $userName = $rawName !== '' ? $this->maskName($rawName) : ('Jugador #' . substr((string) $userId, -4));

            if (! isset($playerBest[$userId]) || $missing < $playerBest[$userId]['missing']) {
                $playerBest[$userId] = ['user_name' => $userName, 'missing' => $missing];
            }
        }

        if (empty($playerBest)) {
            return [];
        }

        $players = [];
        foreach ($playerBest as $userId => $info) {
            $players[] = ['user_id' => $userId, 'user_name' => $info['user_name'], 'missing' => $info['missing']];
        }
        usort($players, fn ($a, $b) => $a['missing'] <=> $b['missing']);

        return $players;
    }

    public function startDraw(BingoRoom $room) : array
    {
        @set_time_limit(0);
        ignore_user_abort(true);

        $roomId = (string) $room->id;

        if (! $room->relationLoaded('config')) {
            $room->load('config');
        }

        // ShouldBeUniqueUntilProcessing libera el lock de cola al comenzar el job.
        // El lock distribuido debe cubrir la duración real del sorteo: 90 bolas
        // a 60 s ya superan los 90 min fijos anteriores, permitiendo que otro
        // worker entre mientras el primer sorteo sigue activo.
        $variant = $room->config?->game_variant ?? BingoVariant::BINGO_75;
        $expectedDrawSeconds = BingoVariant::totalChips($variant) * $this->resolveChipInterval($room);
        $lockTtl = max(
            self::DRAW_LOCK_MIN_TTL_SECONDS,
            $expectedDrawSeconds + self::DRAW_LOCK_SAFETY_MARGIN_SECONDS,
        );
        $drawLock = Cache::lock("bingo:draw-lock:{$roomId}", $lockTtl);

        if (! $drawLock->get()) {
            Log::warning('[BingoDrawService] Otro worker ya tiene el lock del sorteo — abortando duplicado', [
                'room_id' => $roomId,
            ]);

            return ['success' => false, 'room_id' => $roomId, 'reason' => 'lock_held'];
        }

        try {
            return $this->runDraw($room);
        } finally {
            $drawLock->release();
        }
    }

    private function activeCards(BingoRoom $room, array $with) : \Illuminate\Support\Collection
    {
        $roomId = (string) $room->id;
        $withKey = json_encode($with);

        $baseStale = $this->activeCardsCacheRoomId !== $roomId
            || ! isset($this->activeCardsCache[$withKey]);

        $chipIndex = $this->currentChipIndex;
        // Los pagos pueden pasar de PENDING a APPROVED/REJECTED sin cambiar
        // el número total de cartones. Por eso la recarga es temporal, no depende
        // de comparar únicamente el count de ACTIVE.
        $periodicStale = ! $baseStale
            && $chipIndex > 0
            && ($chipIndex % self::ACTIVE_CARDS_REFRESH_INTERVAL === 0);

        if ($baseStale || $periodicStale) {
            $loaded = BingoCard::where('room_id', $roomId)
                ->where('status', BingoCard::STATUS_ACTIVE)
                ->eligibleForDraw()
                ->with($with)
                ->get();

            if ($baseStale) {
                // Primer load o sala distinta — reiniciar todo el cache.
                $this->activeCardsCache = [$withKey => $loaded];
                $this->activeCardsCacheRoomId = $roomId;
            } else {
                // Recarga periódica — recargar TODAS las variantes
                // en cache para evitar que una evicción cause un query extra en el mismo chip
                // (broadcastPlayersProgress y detectWinnerAndEnd usan distintos $with).
                foreach (array_keys($this->activeCardsCache) as $k) {
                    if ($k === $withKey) {
                        $this->activeCardsCache[$k] = $loaded;
                    } else {
                        // Clonar antes de load(): Collection::load() muta en-place y
                        // retorna $this, por lo que sin clone ambas keys quedarían como
                        // el mismo objeto PHP y las relaciones se pisarían mutuamente.
                        $otherWith = json_decode($k, true);
                        $this->activeCardsCache[$k] = (clone $loaded)->load($otherWith);
                    }
                }
            }
        }

        return $this->activeCardsCache[$withKey];
    }

    private function broadcastPlayersProgress(BingoRoom $room, array $patternPositions, array $drawnNumbers) : void
    {
        $players = $this->computePlayersProgress($room, $patternPositions, $drawnNumbers);
        if (! empty($players)) {
            broadcast(new \App\Domains\Games\Bingo\Events\BingoPlayersProgress((int) $room->id, $players));
        }
    }

    /**
     * Si la ronda que acaba de terminar es la última configurada para la sala,
     * cambia el status de la sala a FINISHED y registra finished_at.
     */
    private function closeRoomIfFinalRound(BingoRoom $room, BingoRound $round) : void
    {
        // Obtener número total de rondas desde la configuración de la sala
        $config = $room->relationLoaded('config') ? $room->config : $room->load('config')->config;
        $totalRounds = (int) ($config?->rounds ?? 1);

        // Contar cuántas rondas están ya FINISHED (incluyendo la que acaba de cerrarse)
        $finishedRounds = BingoRound::where('room_id', (string) $room->id)
            ->where('status', 'FINISHED')
            ->count();

        Log::info('[BingoDrawService] Evaluando cierre de sala', [
            'room_id' => $room->id,
            'round_finished' => $round->round,
            'total_rounds' => $totalRounds,
            'finished_count' => $finishedRounds,
        ]);

        if ($finishedRounds < $totalRounds) {
            // Quedan rondas pendientes — no cerrar la sala todavía
            return;
        }

        // Es la ronda final: cambiar status de la sala a FINISHED
        $finishedId = GameStatus::codeToId('FINISHED');

        if (! $finishedId) {
            Log::warning('[BingoDrawService] No existe GameStatus con code=FINISHED — sala no cerrada automáticamente');

            return;
        }

        $room->update([
            'status_id' => $finishedId,
            'finished_at' => now(),
        ]);

        Log::info('[BingoDrawService] Sala cerrada (FINISHED)', [
            'room_id' => $room->id,
            'status_id' => $finishedId,
            'finished_at' => now()->toISOString(),
        ]);

        // Pagar al creador su comisión (+ fondos del premio en Premio/Servicio
        // puras) de las ventas con billetera. Nunca lanza: un fallo queda como
        // alerta de conciliación. Idempotente si el cierre se reintenta.
        try {
            app(\App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomCreatorPayoutService::class)->payoutOnClose($room);
        } catch (Throwable $e) {
            Log::error('[BingoDrawService] Error al pagar al creador de la sala', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $room->refresh();
            $this->statsAggregator->aggregate($room);
        } catch (Throwable $e) {
            Log::warning('[BingoDrawService] Error al calcular estadísticas de sala', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->reportGamesPlayed($room);
    }

    /**
     * Marca la ronda como FINISHED y registra la última ficha.
     */
    private function closeRound(BingoRound $round) : void
    {
        $round->update([
            'status' => 'FINISHED',
            // Filtrar también por round_id para no coger el último chip de otra ronda
            // en juegos multi-ronda con timestamps de segundo granularity.
            'last_chip' => BingoDraw::where('room_id', (string) $round->room_id)
                ->where('round_id', (string) $round->id)
                ->orderBy('created_at', 'desc')
                ->value('chip'),
        ]);

        Log::info('[BingoDrawService] Ronda cerrada', [
            'round_id' => $round->id,
            'round' => $round->round,
        ]);
    }

    /**
     * Cierra la ronda y luego la sala (si es la ronda final) dentro de una
     * transacción para evitar el estado inconsistente "ronda FINISHED + sala IN_PROGRESS".
     * Si closeRoomIfFinalRound() falla, lanza excepción y la transacción revierte
     * el cierre de ronda también — el reintento del job lo reintenta completo.
     */
    private function closeRoundAndRoom(BingoRound $round, BingoRoom $room) : void
    {
        DB::transaction(function() use ($round, $room) {
            $this->closeRound($round);
            $this->closeRoomIfFinalRound($room, $round);
        });
    }

    /**
     * Convierte un monto USD a moneda local usando el exchange rate de la moneda del cartón.
     * Si no hay tasa activa, devuelve el monto USD SIN convertir (mismo número,
     * distinta moneda) — ya no es un fallback silencioso: getActiveRateOrNull()
     * deja constancia en logs, y BingoRoomDetails.vue advierte al admin cuando
     * detecta usd_amount === local_amount en una moneda distinta de USD.
     */
    private function convertToLocal(float $usdAmount, string $currencyId) : float
    {
        $rate = $this->getActiveRateOrNull($currencyId);

        if ($rate === null || $rate <= 0) {
            return $usdAmount;
        }

        return round($usdAmount * $rate, 4);
    }

    private function createDrawRecord(string $roomId, string $roundId, int $chip, int $time, string $variant, array $allDrawnSoFar) : BingoDraw
    {
        $draw = BingoDraw::create([
            'room_id' => $roomId,
            'round_id' => $roundId,
            'chip' => (string) $chip,
            'code' => BingoVariant::chipCode($variant, $chip),
            'time' => $time,
            'status' => 'FINISHED',
        ]);

        // Cache-aside write: usar el array en memoria en lugar de re-leer la DB.
        // Evita la tormenta de lecturas cuando múltiples clientes hacen resync
        // simultáneamente. La clave es la misma que usa BingoGameService::getDrawState().
        Cache::put(
            "bingo:draw-state:draw:{$roomId}:{$roundId}",
            $allDrawnSoFar,
            max($this->currentInterval + 2, 6),
        );

        return $draw;
    }

    private function detectWinnerAndEnd(
        BingoRoom $room,
        BingoRound $round,
        BingoPattern $pattern,
        array $patternPositions,
        array $drawnNumbers,
        int $lastDrawId,
    ) : ?array {
        // Salida rápida: si el patrón no tiene posiciones definidas no hay bingo posible
        if (empty($patternPositions)) {
            return null;
        }

        $drawnSet = array_flip($drawnNumbers);

        $cards = $this->activeCards($room, ['roomUser.user', 'cardLot']);

        // Victoria "de suerte": quedaban pocas bolas por cantar en todo el pool
        // de la variante cuando se completó el patrón (no simplemente "el número
        // recién cantado", porque por diseño del sorteo eso siempre es cierto
        // para cualquier ganador).
        $variant = $room->config?->game_variant ?? BingoVariant::BINGO_75;
        $totalChips = BingoVariant::totalChips($variant);
        $remainingChips = $totalChips - count($drawnNumbers);
        $isLuckyWinner = $remainingChips <= self::LUCKY_WINNER_REMAINING_THRESHOLD;
        $hasFreeCell = BingoVariant::hasFreeCell($variant);

        $winners = [];

        foreach ($cards as $card) {
            $matrix = $card->card_numbers ?? [];

            $hasBingo = true;
            foreach ($patternPositions as $cell) {
                $num = $matrix[$cell['col']][$cell['row']] ?? null;

                // Celda libre del cartón: siempre cuenta como completada. $cell['free']
                // es la posición estática de la variante; cuando la sala permite mover
                // la libre, el null real puede estar en otra celda (ver hasFreeCell).
                if ($cell['free'] || ($hasFreeCell && $num === null)) {
                    continue;
                }

                if ($num === null || ! isset($drawnSet[$num])) {
                    $hasBingo = false;

                    break;
                }
            }

            if (! $hasBingo) {
                continue;
            }

            $winnerData = $this->claimCardWin($room, $round, $pattern, $card->id, $lastDrawId, $isLuckyWinner);
            if ($winnerData !== null) {
                $winners[] = $winnerData;
            }
        }

        if (empty($winners)) {
            return null;
        }

        // Cerrar la ronda y la sala (si es la ronda final) en una sola transacción
        $this->closeRoundAndRoom($round, $room);

        // Evento de fin de juego para toda la sala. 'prize' es el total repartido
        // en este chip — si dos o más cartones completan el patrón a la vez (cada
        // uno con su propio winners[]['prize']), sumarlos aquí evita que este
        // campo resumen quede trunco al monto de un solo ganador; el desglose
        // por ganador sigue disponible en winners[].
        $totalPrize = array_sum(array_column($winners, 'prize'));

        try {
            broadcast(new BingoGameEnded(
                roomId: (int) $room->id,
                drawId: $lastDrawId,
                hasWinner: true,
                totalDrawn: count($drawnNumbers),
                winners: $winners,
                prize: $totalPrize,
                endTime: now()->toISOString(),
            ));
        } catch (Throwable $e) {
            Log::error('[BingoDrawService] Error al emitir BingoGameEnded (con ganador) — sorteo ya cerrado en BD', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->auditLogger->log($room, BingoRoomAuditLogger::EVENT_WINNER_DECLARED, 'system', 'Ganador(es) declarado(s)', [
            'round_id' => (string) $round->id,
            'total_drawn' => count($drawnNumbers),
            'winner_count' => count($winners),
            'prize' => $totalPrize,
            'prize_ids' => array_column($winners, 'card_prize_id'),
        ]);
        $this->auditLogger->log($room, BingoRoomAuditLogger::EVENT_DRAW_FINISHED, 'system', 'Sorteo finalizado con ganador', ['total_drawn' => count($drawnNumbers)]);

        Log::info('[BingoDrawService] Ganador(es) — sorteo detenido', [
            'room_id' => $room->id,
            'round_id' => (string) $round->id,
            'round' => $round->round,
            'pattern' => $pattern->name,
            'drawn' => count($drawnNumbers),
            'winners' => count($winners),
        ]);

        return [
            'success' => true,
            'room_id' => $room->id,
            'total_chips' => count($drawnNumbers),
            'winners' => $winners,
        ];
    }

    /**
     * Genera el orden de las fichas del sorteo con Fisher-Yates usando
     * random_int() (CSPRNG) en vez de shuffle() — shuffle() usa Mersenne
     * Twister, cuyo estado interno es reconstruible a partir de suficientes
     * salidas observadas. Para un sorteo que paga dinero real, el orden de
     * las fichas debe ser impredecible incluso en teoría.
     */
    private function generateSequence(int $totalChips) : array
    {
        $numbers = range(1, $totalChips);
        for ($i = $totalChips - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            if ($i !== $j) {
                [$numbers[$i], $numbers[$j]] = [$numbers[$j], $numbers[$i]];
            }
        }

        return $numbers;
    }

    /**
     * Obtiene la tasa de cambio activa más reciente para una moneda, delegando
     * a la fuente única de verdad (CurrencyExchangeRateService → exchange_rates).
     * Cacheada en memoria por moneda durante la corrida actual del servicio:
     * un sorteo puede tener decenas de cartones ganadores y todos comparten
     * la misma tasa vigente en ese instante.
     *
     * Devuelve null (en vez de lanzar) si no hay tasa activa: un sorteo ya en
     * curso no debe interrumpirse a mitad de camino por esto — el llamador
     * decide cómo proceder (ver convertToLocal) y siempre queda registrado.
     */
    private function getActiveRateOrNull(string $currencyId) : ?float
    {
        if (array_key_exists($currencyId, $this->rateCache)) {
            return $this->rateCache[$currencyId];
        }

        try {
            return $this->rateCache[$currencyId] = $this->currencyExchangeRate->getActiveRate($currencyId);
        } catch (ExchangeRateException $e) {
            Log::error('[BingoDrawService] Sin tasa de cambio activa al calcular premio — monto quedará SIN CONVERTIR (revisar manualmente)', [
                'currency_id' => $currencyId,
                'error' => $e->getMessage(),
            ]);

            return $this->rateCache[$currencyId] = null;
        }
    }

    /** Invalida el cache de cartones activos — usar tras cambiar el status de un cartón. */
    private function invalidateActiveCardsCache() : void
    {
        $this->activeCardsCache = [];
        // No resetear currentChipIndex — lo actualiza el draw loop por chip.
    }

    /**
     * Resuelve el monto del premio (USD y moneda local) para un cartón ganador.
     *
     * Si la ronda es de tipo FIXED y existe un valor configurado por moneda
     * (`bingo_round_prizes`) para la moneda del cartón, se usa ese valor
     * (en moneda local), derivando el equivalente en USD mediante la tasa activa.
     * En cualquier otro caso, se usa el bote de la sala (`prize_pool`, en USD).
     */
    private function maskName(string $name) : string
    {
        $parts = preg_split('/\s+/', trim($name));
        $first = $parts[0] ?? '';
        $visible = mb_substr($first, 0, 4);

        return $visible . str_repeat('*', max(0, mb_strlen($first) - 4))
            . (isset($parts[1]) ? ' ' . mb_substr($parts[1], 0, 1) . '.' : '');
    }

    private function persistDrawCodes(string $roomId, string $roundId, array $sequence) : void
    {
        $now = now();

        $rows = array_map(
            fn ($index, $number) => [
                'id' => BingoDrawCode::generateSnowflakeId(),
                'room_id' => $roomId,
                'round_id' => $roundId,
                'num' => $index + 1,
                'sym' => (string) $number,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_keys($sequence),
            $sequence,
        );

        BingoDrawCode::insert($rows);
    }

    /**
     * Registra "partida jugada" (XP/logros) para cada jugador de la sala que ya
     * cerró. Los ganadores ya recibieron su XP de victoria en claimCardWin(); aquí
     * solo se les evita duplicar la entrada de "partida jugada" via reportParticipation,
     * que ya la registra junto con el XP de victoria. Vendedores/administradores de
     * la sala (role SELLER/ADMIN) no cuentan como jugadores para este propósito.
     */
    private function reportGamesPlayed(BingoRoom $room) : void
    {
        try {
            $players = BingoRoomUser::where('room_id', (string) $room->id)->get()
                ->filter(fn (BingoRoomUser $ru) => $ru->isPlayer());

            $xpForPlaying = $this->userLevelService->calculateXpReward('game_played');

            foreach ($players as $roomUser) {
                if ($roomUser->winner === 'Y') {
                    // Ya se registró en claimCardWin() con won=true.
                    continue;
                }

                $user = $roomUser->user;
                if (! $user) {
                    continue;
                }

                $this->gameCompletionService->reportParticipation(
                    user: $user,
                    game: GameParticipation::GAME_BINGO,
                    won: false,
                    xp: $xpForPlaying,
                    roomId: (string) $room->id,
                );
            }
        } catch (Throwable $e) {
            Log::warning('[BingoDrawService] Error al registrar partidas jugadas de la sala', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolvePrizeAmounts(BingoRoom $room, BingoRound $round, BingoCard $card) : array
    {
        if ($round->prize_type === 'FIXED') {
            // prizes pre-cargados por claimCardWin() antes de abrir la TX
            $fixedPrize = $round->prizes->firstWhere('currency_id', (string) $card->currency_id);

            if ($fixedPrize) {
                $localAmount = (float) $fixedPrize->prize_value;
                $rate = $this->getActiveRateOrNull((string) $card->currency_id);
                $usdAmount = ($rate !== null && $rate > 0) ? round($localAmount / $rate, 4) : $localAmount;

                return ['usd_amount' => $usdAmount, 'local_amount' => $localAmount];
            }

            // Sin precio configurado para la moneda del cartón — sin pago monetario
            Log::warning('[BingoDrawService] Ronda FIXED sin precio para la moneda del cartón ganador', [
                'round_id' => $round->id,
                'room_id' => $room->id,
                'currency_id' => $card->currency_id,
            ]);

            return ['usd_amount' => 0.0, 'local_amount' => 0.0];
        }

        // Rondas de tipo OTHER son premios físicos — sin monto monetario
        if ($round->prize_type === 'OTHER') {
            return ['usd_amount' => 0.0, 'local_amount' => 0.0];
        }

        $totalUsd = (float) ($room->prize_pool ?? 0);

        // Para rondas PCT, el porcentaje está en bingo_round_prizes.prize_value (sin currency_id)
        if ($round->prize_type === 'PCT') {
            // prizes pre-cargados por claimCardWin() antes de abrir la TX
            $pctEntry = $round->prizes->firstWhere('currency_id', null);
            if ($pctEntry) {
                $pct = (float) $pctEntry->prize_value;
            } else {
                // Fallback 1: rounds_pct del config ÷ número de rondas
                // Fallback 2: división igualitaria del 100%
                $totalRounds = max(1, (int) ($room->config?->rounds ?? 1));
                $pct = $room->config?->rounds_pct !== null
                    ? (float) $room->config->rounds_pct / $totalRounds
                    : (100.0 / $totalRounds);

                Log::warning('[BingoDrawService] Ronda PCT sin entrada en bingo_round_prizes — usando fallback', [
                    'round_id' => $round->id,
                    'room_id' => $room->id,
                    'fallback_pct' => $pct,
                ]);
            }
            $usdAmount = round($totalUsd * ($pct / 100.0), 4);
        } else {
            $totalRounds = max(1, (int) ($room->config?->rounds ?? 1));
            $usdAmount = round($totalUsd / $totalRounds, 4);
        }

        return ['usd_amount' => $usdAmount, 'local_amount' => $this->convertToLocal($usdAmount, (string) $card->currency_id)];
    }

    /**
     * Intervalo efectivo entre bolas, compartido por el TTL del lock y el loop.
     */
    private function resolveChipInterval(BingoRoom $room) : int
    {
        $configInterval = (int) ($room->config?->time_chip ?? 0);

        return $configInterval > 0
            ? $configInterval
            : (app()->environment('local')
                ? (int) env('BINGO_DEV_INTERVAL_SECONDS', self::CHIP_INTERVAL_DEV)
                : self::CHIP_INTERVAL_SECONDS);
    }

    private function runDraw(BingoRoom $room) : array
    {
        // Limpiar caches de instancia al inicio de cada sorteo para que los datos
        // de una sala no se filtren a sorteos posteriores en el mismo worker process.
        $this->rateCache = [];
        $this->invalidateActiveCardsCache();

        $roomId = $room->id;

        // Asegurar que config esté cargada
        if (! $room->relationLoaded('config')) {
            $room->load('config');
        }

        $variant = $room->config?->game_variant ?? BingoVariant::BINGO_75;
        $totalChips = BingoVariant::totalChips($variant);

        Log::info('[BingoDrawService] Iniciando sorteo', ['room_id' => $roomId, 'variant' => $variant]);

        // 1. Resolver ronda activa, crear si no existe
        $round = BingoRound::where('room_id', (string) $roomId)
            ->whereIn('status', ['ACTIVE', 'IN_PROGRESS'])
            ->orderBy('round')
            ->first();

        // Si no hay ronda ACTIVE/IN_PROGRESS pero ya existen rondas FINISHED, la sala
        // completó su sorteo (con o sin ganador) y no debe volver a sortear — esto
        // pasaba cuando el cron bingo:start-pending-draws seguía despachando la sala
        // porque closeRoomIfFinalRound() no logró cerrarla (p.ej. falta el GameStatus
        // 'FINISHED'), generando un BingoCardPrize y un pago nuevos en cada corrida.
        if (! $round && BingoRound::where('room_id', (string) $roomId)->where('status', 'FINISHED')->exists()) {
            Log::warning('[BingoDrawService] Sorteo ya completado — se omite re-sorteo', ['room_id' => $roomId]);

            return ['success' => true, 'room_id' => $roomId, 'total_chips' => 0, 'winner' => null, 'alreadyFinished' => true];
        }

        if (! $round) {
            $round = BingoRound::where('room_id', (string) $roomId)
                ->orderBy('round')
                ->first();
        }

        if (! $round) {
            $config = $room->relationLoaded('config') ? $room->config : $room->load('config')->config;
            if (! $config) {
                throw new DomainException('La sala no tiene configuración ni rondas.');
            }
            $defaultPattern = \App\Domains\Games\Bingo\Models\Pattern\BingoPattern::where('default_pattern', 'Y')->first()
                ?? \App\Domains\Games\Bingo\Models\Pattern\BingoPattern::first();
            if (! $defaultPattern) {
                throw new DomainException('No hay patrones de bingo configurados.');
            }
            $round = BingoRound::create([
                'room_id' => (string) $roomId,
                'room_config_id' => (string) $config->id,
                'round' => 1,
                'bingo_pattern_id' => (string) $defaultPattern->id,
                'prize_type' => 'PCT',
                'status' => 'ACTIVE',
            ]);
        }

        // Persistir el inicio antes de emitir la primera ficha. Esto bloquea
        // aprobaciones tardías de comprobantes aunque la sala se haya iniciado
        // manualmente antes de su hora programada.
        if ($round->status !== 'IN_PROGRESS') {
            DB::transaction(function() use ($roomId, $round) {
                // Serializar el inicio contra aprobaciones/rechazos de comprobantes.
                // approve() bloquea primero el pago y luego consulta hasStarted(); al
                // tomar aquí los mismos pagos PENDING antes de marcar la ronda,
                // cualquier aprobación concurrente queda ordenada antes del inicio
                // (se acepta como previa) o después (exige confirmLate).
                // Ordenar por id mantiene el mismo orden de locks que claimCardWin().
                BingoCardPayment::where('room_id', (string) $roomId)
                    ->where('payment_status', 'PENDING')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id']);

                $lockedRound = BingoRound::whereKey($round->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedRound->status !== 'IN_PROGRESS') {
                    $lockedRound->forceFill([
                        'status' => 'IN_PROGRESS',
                        'start_time' => $lockedRound->start_time ?? now(),
                    ])->save();
                }

                $round->refresh();
            });
        }

        $roundId = (string) $round->id;

        // 3. Cargar el patrón de la ronda (determina qué celdas deben completarse)
        $pattern = BingoPattern::find($round->bingo_pattern_id);
        if (! $pattern) {
            throw new DomainException("La ronda #{$round->round} no tiene patrón de bingo asociado.");
        }

        // Precalcular las posiciones del patrón una sola vez
        $patternPositions = $this->patternResolver->resolve($pattern, $variant);

        Log::info('[BingoDrawService] Patrón de la ronda cargado', [
            'round_id' => $roundId,
            'pattern_id' => $pattern->id,
            'pattern' => $pattern->name,
            'type' => $pattern->type_pattern,
            'positions' => $patternPositions,
        ]);

        // 2. ¿Hay un sorteo previo interrumpido para esta sala/ronda? (p.ej. el worker
        // se reinició a mitad del sorteo). Si la secuencia ya fue persistida y quedan
        // fichas por cantar, retomamos desde ahí en vez de reiniciar desde cero.
        // Filtrado por room_id + round_id: bingo_draws_code.round_id (agregado en
        // 2026_07_13_01) evita que códigos huérfanos de una ronda anterior se
        // confundan con la secuencia de la ronda actual.
        $existingSequence = BingoDrawCode::where('room_id', (string) $roomId)
            ->where('round_id', $roundId)
            ->orderBy('num')
            ->pluck('sym')
            ->map(fn ($sym) => (int) $sym)
            ->all();

        $alreadyDrawn = BingoDraw::where('room_id', (string) $roomId)
            ->where('round_id', $roundId)
            ->orderBy('time')
            ->orderBy('created_at')
            ->get();

        // Defensa en profundidad además del filtro por round_id: la secuencia debe
        // ser una permutación íntegra de 1..totalChips (sin duplicados/huecos) antes
        // de confiar en ella para resumir — cubre corrupción manual de la tabla o
        // filas legacy previas a la migración que agregó round_id.
        $sequenceIsValid = count($existingSequence) === $totalChips
            && count(array_unique($existingSequence)) === $totalChips
            && ! array_diff($existingSequence, range(1, $totalChips));

        if (! empty($existingSequence) && ! $sequenceIsValid) {
            Log::error('[BingoDrawService] Secuencia persistida inválida — se descarta y regenera', [
                'room_id' => $roomId,
                'round_id' => $roundId,
                'found' => count($existingSequence),
                'expected' => $totalChips,
            ]);
        }

        // Además de que la secuencia sea válida, verificar que las fichas ya cantadas
        // coincidan exactamente con el prefijo de esa secuencia — protege contra el
        // caso donde la secuencia fue regenerada pero los draws viejos no se borraron,
        // lo que causaría fichas fantasma o fichas saltadas en el resume.
        $drawnChips = $alreadyDrawn->pluck('chip')->map(fn ($c) => (int) $c)->all();
        $sequencePrefix = array_slice($existingSequence, 0, count($drawnChips));
        $prefixMatches = $drawnChips === $sequencePrefix;

        // Caso especial: todos los chips ya fueron cantados pero closeRound() no se ejecutó
        // (p.ej. el worker murió justo después de crear el último BingoDraw).
        // Con la condición anterior ($alreadyDrawn->count() < count($existingSequence)),
        // este caso caía al else y borraba todo el historial — error crítico que obligaba
        // a re-sortear una sala que ya había terminado. Ahora lo detectamos y cerramos directamente.
        $allDone = $sequenceIsValid
            && $alreadyDrawn->count() === count($existingSequence)
            && $alreadyDrawn->isNotEmpty();

        $resuming = $sequenceIsValid
            && $alreadyDrawn->isNotEmpty()
            && $alreadyDrawn->count() < count($existingSequence)
            && $prefixMatches;

        if ($sequenceIsValid && $alreadyDrawn->isNotEmpty() && ! $prefixMatches) {
            Log::warning('[BingoDrawService] Fichas cantadas no coinciden con el prefijo de la secuencia — reiniciando sorteo', [
                'room_id' => $roomId,
                'round_id' => $roundId,
                'drawn_count' => count($drawnChips),
                'prefix_expected' => array_slice($sequencePrefix, 0, 5),
                'drawn_actual' => array_slice($drawnChips, 0, 5),
            ]);
        }

        // Resolver el intervalo aquí — antes del bloque if($resuming) — para que
        // el TTL del cache de resume y el lock distribuido usen el mismo valor.
        $this->currentInterval = $this->resolveChipInterval($room);

        if ($allDone) {
            // Todos los chips ya están en bingo_draws pero la ronda/sala no se cerró.
            // Cerrar directamente sin re-sortear ni borrar el historial.
            $drawnSoFar = $drawnChips;
            $lastDraw = $alreadyDrawn->last();

            Log::warning('[BingoDrawService] Sorteo completo sin cierre detectado — cerrando ronda', [
                'room_id' => $roomId,
                'round_id' => $roundId,
                'total_drawn' => count($drawnSoFar),
            ]);

            Cache::put("bingo:draw-state:draw:{$roomId}:{$roundId}", $drawnSoFar, max($this->currentInterval + 2, 6));

            // Verificar si ya hay ganadores registrados antes de llamar detectWinnerAndEnd.
            // Si existen BingoCardPrize para esta ronda, sólo cerramos sin re-declarar.
            $hasExistingPrizes = \App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize::where('round_id', $roundId)->exists();

            if ($hasExistingPrizes) {
                Log::info('[BingoDrawService] All-done: ganadores ya registrados — cerrando sin re-declarar', [
                    'room_id' => $roomId,
                    'round_id' => $roundId,
                ]);
                $this->closeRoundAndRoom($round, $room);

                return ['success' => true, 'room_id' => $roomId, 'total_chips' => count($drawnSoFar), 'winner' => null, 'alreadyFinished' => true];
            }

            if ($result = $this->detectWinnerAndEnd($room, $round, $pattern, $patternPositions, $drawnSoFar, (int) $lastDraw->id)) {
                return $result;
            }

            // Sin ganador tras revisar todos los chips
            $this->closeRoundAndRoom($round, $room);
            broadcast(new BingoGameEnded(
                roomId: (int) $roomId,
                drawId: (int) ($lastDraw->id ?? 0),
                hasWinner: false,
                totalDrawn: count($drawnSoFar),
                winners: [],
                prize: (float) ($room->prize_pool ?? 0),
                endTime: now()->toISOString(),
            ));
            $this->auditLogger->log($room, BingoRoomAuditLogger::EVENT_DRAW_FINISHED, 'system', 'Sorteo completado sin ganador (all-done recovery)', ['total_drawn' => count($drawnSoFar)]);

            return ['success' => true, 'room_id' => $roomId, 'total_chips' => count($drawnSoFar), 'winner' => null];
        }
        if ($resuming) {
            $sequence = $existingSequence;
            $drawnSoFar = $drawnChips;
            $lastDraw = $alreadyDrawn->last();
            $startIndex = count($drawnSoFar);

            Log::warning('[BingoDrawService] Retomando sorteo interrumpido', [
                'room_id' => $roomId,
                'round_id' => $roundId,
                'ya_cantadas' => $startIndex,
                'total' => count($sequence),
            ]);

            // Refrescar cache antes del broadcast: clientes que hagan resync HTTP
            // justo durante el resume recibirán el snapshot correcto en lugar de
            // un cache expirado o del crash anterior. TTL provisional hasta que el
            // loop de fichas lo refresque con el intervalo correcto.
            Cache::put("bingo:draw-state:draw:{$roomId}:{$roundId}", $drawnSoFar, max($this->currentInterval + 2, 6));

            // Reemitir GameStarted al recuperar: el intento anterior pudo haber
            // persistido la primera bola y fallado antes de entregar este evento.
            // Los clientes deben tratar GameStarted como idempotente por draw_id.
            $firstDraw = $alreadyDrawn->first();
            broadcast(new BingoGameStarted((int) $roomId, (int) $firstDraw->id));
            broadcast(new BingoNumberCalled((int) $lastDraw->chip, $roomId));

            // Verificar si ya hay ganadores antes de llamar detectWinnerAndEnd en resume,
            // para evitar re-declarar un ganador ya procesado y re-emitir BingoWinnerDeclared.
            // IMPORTANTE: si hay premios existentes y el resume termina sin detectar un nuevo
            // ganador, el loop de fichas restantes caería al bloque "sin ganador" y emitiría
            // BingoGameEnded(hasWinner:false) — incorrecto. Por eso retornamos aquí directamente
            // para que el draw job finalice correctamente y el payout sea retomado por el cron.
            $hasExistingPrizes = \App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize::where('round_id', $roundId)->exists();

            if ($hasExistingPrizes) {
                Log::info('[BingoDrawService] Resume: ganadores ya registrados — cerrando ronda sin re-declarar', [
                    'room_id' => $roomId,
                    'round_id' => $roundId,
                ]);
                $this->closeRoundAndRoom($round, $room);

                return ['success' => true, 'room_id' => $roomId, 'total_chips' => count($drawnSoFar), 'winner' => null, 'alreadyFinished' => true];
            }
            if ($result = $this->detectWinnerAndEnd($room, $round, $pattern, $patternPositions, $drawnSoFar, (int) $lastDraw->id)) {
                return $result;
            }
        } else {
            // Limpiar sorteos previos de la ronda activa únicamente — no tocar
            // draws de rondas ya FINISHED para no destruir el historial de auditoría
            // en juegos multi-ronda.
            DB::transaction(function() use ($roomId, $roundId) {
                BingoDraw::where('room_id', $roomId)->where('round_id', $roundId)->delete();
                BingoDrawCode::where('room_id', $roomId)->where('round_id', $roundId)->delete();
            });

            // 4. Generar y persistir secuencia
            $sequence = $this->generateSequence($totalChips);
            $this->persistDrawCodes($roomId, $roundId, $sequence);

            // 5. Primera ficha + GameStarted
            $firstDraw = $this->createDrawRecord($roomId, $roundId, $sequence[0], 0, $variant, [$sequence[0]]);

            try {
                broadcast(new BingoGameStarted((int) $roomId, (int) $firstDraw->id));
                broadcast(new BingoNumberCalled((int) $sequence[0], $roomId));
            } catch (Throwable $e) {
                Log::error('[BingoDrawService] Falló la emisión inicial — se detiene para reintentar desde la ficha persistida', [
                    'room_id' => $roomId,
                    'draw_id' => $firstDraw->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
            $this->auditLogger->log($room, BingoRoomAuditLogger::EVENT_DRAW_STARTED, 'system', 'Sorteo iniciado', ['round_id' => (string) $roundId, 'variant' => $variant]);

            $drawnSoFar = [$sequence[0]];
            $startIndex = 1;

            if ($result = $this->detectWinnerAndEnd($room, $round, $pattern, $patternPositions, $drawnSoFar, $firstDraw->id)) {
                return $result;
            }
        }

        // 6. Fichas restantes
        // $this->currentInterval ya fue resuelto antes del bloque resume (arriba).
        $interval = $this->currentInterval;

        for ($i = $startIndex, $total = count($sequence); $i < $total; $i++) {
            // sleep() intencional — este método siempre corre en el worker dedicado
            // 'bingo-draw' (StartBingoDrawJob, $timeout=0), nunca en el servidor web.
            if ($interval > 0) {
                sleep($interval);
            }

            $this->currentChipIndex = $i;

            $chip = $sequence[$i];
            // time = segundos transcurridos desde la primera ficha (índice * intervalo).
            // Nunca 0 para que el ORDER BY time preserve el orden de inserción.
            // ORDEN CRÍTICO: persist → broadcast → append a $drawnSoFar → detectWinner.
            // detectWinnerAndEnd ve el chip solo después de que el broadcast salió,
            // evitando un payout por una ficha que los clientes nunca recibieron.
            $draw = $this->createDrawRecord($roomId, $roundId, $chip, $i * max($interval, 1), $variant, [...$drawnSoFar, $chip]);

            try {
                broadcast(new BingoNumberCalled((int) $chip, $roomId));
            } catch (Throwable $e) {
                Log::error('[BingoDrawService] Falló la emisión de una ficha — se detiene para reintentar desde la ficha persistida', [
                    'room_id' => $roomId,
                    'draw_id' => $draw->id,
                    'chip' => $chip,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
            // Append después del broadcast: detectWinnerAndEnd no verá el chip
            // hasta aquí, garantizando que los clientes ya lo recibieron.
            $drawnSoFar[] = $chip;

            if (($totalChips - count($drawnSoFar)) <= 10) {
                try {
                    $this->broadcastPlayersProgress($room, $patternPositions, $drawnSoFar);
                } catch (Throwable $e) {
                    Log::warning('[BingoDrawService] Error al emitir progreso de jugadores', [
                        'room_id' => $roomId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($result = $this->detectWinnerAndEnd($room, $round, $pattern, $patternPositions, $drawnSoFar, $draw->id)) {
                return $result;
            }
        }

        // 7. Sorteo completo sin ganador — cerrar ronda y sala si es la final
        $lastDraw = BingoDraw::where('room_id', (string) $roomId)
            ->where('round_id', $roundId)
            ->orderBy('created_at', 'desc')
            ->first();

        $this->closeRoundAndRoom($round, $room);

        broadcast(new BingoGameEnded(
            roomId: (int) $roomId,
            drawId: (int) ($lastDraw?->id ?? 0),
            hasWinner: false,
            totalDrawn: count($drawnSoFar),
            winners: [],
            prize: (float) ($room->prize_pool ?? 0),
            endTime: now()->toISOString(),
        ));

        $this->auditLogger->log($room, BingoRoomAuditLogger::EVENT_DRAW_FINISHED, 'system', 'Sorteo completado sin ganador', ['total_drawn' => count($drawnSoFar)]);

        Log::info('[BingoDrawService] Sorteo completado sin ganador', [
            'room_id' => $roomId,
            'drawn' => count($drawnSoFar),
            'pattern' => $pattern->name,
        ]);

        return ['success' => true, 'room_id' => $roomId, 'total_chips' => count($drawnSoFar), 'winner' => null];
    }
}
