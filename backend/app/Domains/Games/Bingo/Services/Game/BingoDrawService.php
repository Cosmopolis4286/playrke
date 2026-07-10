<?php

namespace App\Domains\Games\Bingo\Services\Game;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Draw\BingoDrawCode;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Domains\Games\Bingo\Events\BingoGameEnded;
use App\Domains\Games\Bingo\Events\BingoGameStarted;
use App\Domains\Games\Bingo\Events\BingoNumberCalled;
use App\Domains\Games\Bingo\Events\BingoWinnerDeclared;
use App\Domains\Platform\WinEvent\Services\WinEventService;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Domains\Games\Bingo\Services\Winner\BingoPrizePayoutService;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class BingoDrawService
{
    private const CHIP_INTERVAL_SECONDS = 5;
    private const CHIP_INTERVAL_DEV     = 0; // sin espera en entorno local

    /** Cache de tasas de cambio activas resuelta por moneda durante la corrida actual. */
    private array $rateCache = [];

    public function __construct(
        private readonly BingoPrizePayoutService $prizePayoutService,
        private readonly WinEventService $winEventService,
        private readonly BingoRoomAuditLogger $auditLogger,
        private readonly BingoPatternPositionResolver $patternResolver = new BingoPatternPositionResolver(),
    ) {}

    public function startDraw(BingoRoom $room): array
    {
        @set_time_limit(0);
        ignore_user_abort(true);

        // Limpiar caches de instancia al inicio de cada sorteo para que los datos
        // de una sala no se filtren a sorteos posteriores en el mismo worker process.
        $this->rateCache = [];
        $this->invalidateActiveCardsCache();

        $roomId  = $room->id;

        // Asegurar que config esté cargada
        if (!$room->relationLoaded('config')) {
            $room->load('config');
        }

        $variant    = $room->config?->game_variant ?? BingoVariant::BINGO_75;
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
        if (!$round && BingoRound::where('room_id', (string) $roomId)->where('status', 'FINISHED')->exists()) {
            Log::warning('[BingoDrawService] Sorteo ya completado — se omite re-sorteo', ['room_id' => $roomId]);
            return ['success' => true, 'room_id' => $roomId, 'total_chips' => 0, 'winner' => null, 'alreadyFinished' => true];
        }

        if (!$round) {
            $round = BingoRound::where('room_id', (string) $roomId)
                ->orderBy('round')
                ->first();
        }

        if (!$round) {
            $config = $room->relationLoaded('config') ? $room->config : $room->load('config')->config;
            if (!$config) {
                throw new \DomainException('La sala no tiene configuración ni rondas.');
            }
            $defaultPattern = \App\Domains\Games\Bingo\Models\Pattern\BingoPattern::where('default_pattern', 'Y')->first()
                ?? \App\Domains\Games\Bingo\Models\Pattern\BingoPattern::first();
            if (!$defaultPattern) {
                throw new \DomainException('No hay patrones de bingo configurados.');
            }
            $round = BingoRound::create([
                'room_id'          => (string) $roomId,
                'room_config_id'   => (string) $config->id,
                'round'            => 1,
                'bingo_pattern_id' => (string) $defaultPattern->id,
                'prize_type'       => 'PCT',
                'status'           => 'ACTIVE',
            ]);
        }

        $roundId = (string) $round->id;

        // 3. Cargar el patrón de la ronda (determina qué celdas deben completarse)
        $pattern = BingoPattern::find($round->bingo_pattern_id);
        if (!$pattern) {
            throw new \DomainException("La ronda #{$round->round} no tiene patrón de bingo asociado.");
        }

        // Precalcular las posiciones del patrón una sola vez
        $patternPositions = $this->patternResolver->resolve($pattern, $variant);

        Log::info('[BingoDrawService] Patrón de la ronda cargado', [
            'round_id'    => $roundId,
            'pattern_id'  => $pattern->id,
            'pattern'     => $pattern->name,
            'type'        => $pattern->type_pattern,
            'positions'   => $patternPositions,
        ]);

        // 2. ¿Hay un sorteo previo interrumpido para esta sala/ronda? (p.ej. el worker
        // se reinició a mitad del sorteo). Si la secuencia ya fue persistida y quedan
        // fichas por cantar, retomamos desde ahí en vez de reiniciar desde cero.
        $existingSequence = BingoDrawCode::where('room_id', (string) $roomId)
            ->orderBy('num')
            ->pluck('sym')
            ->map(fn ($sym) => (int) $sym)
            ->all();

        $alreadyDrawn = BingoDraw::where('room_id', (string) $roomId)
            ->where('round_id', $roundId)
            ->orderBy('time')
            ->orderBy('created_at')
            ->get();

        $resuming = !empty($existingSequence) && $alreadyDrawn->isNotEmpty() && $alreadyDrawn->count() < count($existingSequence);

        if ($resuming) {
            $sequence   = $existingSequence;
            $drawnSoFar = $alreadyDrawn->pluck('chip')->map(fn ($chip) => (int) $chip)->all();
            $lastDraw   = $alreadyDrawn->last();
            $startIndex = count($drawnSoFar);

            Log::warning('[BingoDrawService] Retomando sorteo interrumpido', [
                'room_id'       => $roomId,
                'round_id'      => $roundId,
                'ya_cantadas'   => $startIndex,
                'total'         => count($sequence),
            ]);

            broadcast(new BingoNumberCalled((int) $lastDraw->chip, $roomId));

            if ($result = $this->detectWinnerAndEnd($room, $round, $pattern, $patternPositions, $drawnSoFar, (int) $lastDraw->id, $variant)) {
                return $result;
            }
        } else {
            // Limpiar sorteos previos (de una sala/ronda distinta o ya finalizada)
            DB::transaction(function () use ($roomId) {
                BingoDraw::where('room_id', $roomId)->delete();
                BingoDrawCode::where('room_id', $roomId)->delete();
            });

            // 4. Generar y persistir secuencia
            $sequence = $this->generateSequence($totalChips);
            $this->persistDrawCodes($roomId, $sequence);

            // 5. Primera ficha + GameStarted
            $firstDraw = $this->createDrawRecord($roomId, $roundId, $sequence[0], 0, $variant);
            broadcast(new BingoGameStarted($roomId, (int) $firstDraw->id));
            broadcast(new BingoNumberCalled((int) $sequence[0], $roomId));
            $this->auditLogger->log($room, BingoRoomAuditLogger::EVENT_DRAW_STARTED, 'system', 'Sorteo iniciado', ['round_id' => (string) $roundId, 'variant' => $variant]);

            $drawnSoFar = [$sequence[0]];
            $startIndex = 1;

            if ($result = $this->detectWinnerAndEnd($room, $round, $pattern, $patternPositions, $drawnSoFar, $firstDraw->id, $variant)) {
                return $result;
            }
        }

        // 6. Fichas restantes
        for ($i = $startIndex, $total = count($sequence); $i < $total; $i++) {
            $interval = app()->environment('local') ? self::CHIP_INTERVAL_DEV : self::CHIP_INTERVAL_SECONDS;
            if ($interval > 0) sleep($interval);

            $this->currentChipIndex = $i;

            $chip = $sequence[$i];
            $draw = $this->createDrawRecord($roomId, $roundId, $chip, $i * $interval, $variant);

            broadcast(new BingoNumberCalled((int) $chip, $roomId));
            $drawnSoFar[] = $chip;

            if (($totalChips - count($drawnSoFar)) <= 10) {
                try {
                    $this->broadcastPlayersProgress($room, $patternPositions, $drawnSoFar, $variant);
                } catch (\Throwable $e) {
                    Log::warning('[BingoDrawService] Error al emitir progreso de jugadores', [
                        'room_id' => $roomId,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }

            if ($result = $this->detectWinnerAndEnd($room, $round, $pattern, $patternPositions, $drawnSoFar, $draw->id, $variant)) {
                return $result;
            }
        }

        // 7. Sorteo completo sin ganador — cerrar ronda y sala si es la final
        $lastDraw = BingoDraw::where('room_id', (string) $roomId)->orderBy('created_at', 'desc')->first();

        $this->closeRound($round);
        $this->closeRoomIfFinalRound($room, $round);

        broadcast(new BingoGameEnded(
            roomId:     (int) $roomId,
            drawId:     (int) ($lastDraw?->id ?? 0),
            hasWinner:  false,
            totalDrawn: count($drawnSoFar),
            winners:    [],
            prize:      (float) ($room->prize_pool ?? 0),
            endTime:    now()->toISOString(),
        ));

        $this->auditLogger->log($room, BingoRoomAuditLogger::EVENT_DRAW_FINISHED, 'system', 'Sorteo completado sin ganador', ['total_drawn' => count($drawnSoFar)]);

        Log::info('[BingoDrawService] Sorteo completado sin ganador', [
            'room_id' => $roomId,
            'drawn'   => count($drawnSoFar),
            'pattern' => $pattern->name,
        ]);

        return ['success' => true, 'room_id' => $roomId, 'total_chips' => count($drawnSoFar), 'winner' => null];
    }

    /** Número de fichas entre cada verificación de compras mid-sorteo. */
    private const ACTIVE_CARDS_REFRESH_INTERVAL = 5;

    /**
     * Índice de la ficha actual dentro del sorteo (0-based).
     * El draw loop lo actualiza una vez por chip antes de llamar a broadcastPlayersProgress
     * y detectWinnerAndEnd, de modo que el intervalo periódico de activeCards() sea
     * "cada N chips reales", no "cada N llamadas al método".
     */
    private int $currentChipIndex = 0;

    /**
     * Cartones ACTIVE de la sala, cacheados en memoria durante la corrida del sorteo.
     * Keyed por firma de relaciones ($with) para evitar que broadcastPlayersProgress
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
    private ?int $activeCardsCacheCount = null;

    private function activeCards(BingoRoom $room, array $with): \Illuminate\Support\Collection
    {
        $roomId   = (string) $room->id;
        $withKey  = implode(',', $with);

        $baseStale = $this->activeCardsCacheRoomId !== $roomId
            || !isset($this->activeCardsCache[$withKey]);

        $chipIndex = $this->currentChipIndex;
        $periodicStale = !$baseStale
            && $this->activeCardsCacheCount !== null
            && $chipIndex > 0
            && ($chipIndex % self::ACTIVE_CARDS_REFRESH_INTERVAL === 0)
            && BingoCard::where('room_id', $roomId)->where('status', BingoCard::STATUS_ACTIVE)->count() !== $this->activeCardsCacheCount;

        if ($baseStale || $periodicStale) {
            $loaded = BingoCard::where('room_id', $roomId)
                ->where('status', BingoCard::STATUS_ACTIVE)
                ->with($with)
                ->get();

            if ($baseStale) {
                // Primer load o sala distinta — reiniciar todo el cache.
                $this->activeCardsCache        = [$withKey => $loaded];
                $this->activeCardsCacheRoomId  = $roomId;
                $this->activeCardsCacheCount   = $loaded->count();
            } else {
                // Recarga periódica — actualizar el count global y esta variante.
                $this->activeCardsCache[$withKey] = $loaded;
                $this->activeCardsCacheCount      = $loaded->count();
                // Recargar las demás variantes con los mismos cartones pero distintas relaciones.
                foreach (array_keys($this->activeCardsCache) as $k) {
                    if ($k !== $withKey) {
                        unset($this->activeCardsCache[$k]);
                    }
                }
            }
        }

        return $this->activeCardsCache[$withKey];
    }

    /** Invalida el cache de cartones activos — usar tras cambiar el status de un cartón. */
    private function invalidateActiveCardsCache(): void
    {
        $this->activeCardsCache      = [];
        $this->activeCardsCacheCount = null;
        // No resetear currentChipIndex — lo actualiza el draw loop por chip.
    }

    private function generateSequence(int $totalChips): array
    {
        $numbers = range(1, $totalChips);
        shuffle($numbers);
        return $numbers;
    }

    private function persistDrawCodes(string $roomId, array $sequence): void
    {
        $now = now();

        $rows = array_map(
            fn ($index, $number) => [
                'id'         => BingoDrawCode::generateSnowflakeId(),
                'room_id'    => $roomId,
                'num'        => $index + 1,
                'sym'        => (string) $number,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_keys($sequence),
            $sequence
        );

        BingoDrawCode::insert($rows);
    }

    private function createDrawRecord(string $roomId, string $roundId, int $chip, int $time, string $variant): BingoDraw
    {
        return BingoDraw::create([
            'room_id'  => $roomId,
            'round_id' => $roundId,
            'chip'     => (string) $chip,
            'code'     => BingoVariant::chipCode($variant, $chip),
            'time'     => $time,
            'status'   => 'FINISHED',
        ]);
    }

    private function broadcastPlayersProgress(BingoRoom $room, array $patternPositions, array $drawnNumbers, string $variant = BingoVariant::BINGO_75): void
    {
        if (empty($patternPositions)) {
            return;
        }

        $drawnSet = array_flip($drawnNumbers);

        $cards = $this->activeCards($room, ['roomUser.user']);

        $playerBest = []; // user_id => ['user_name' => ..., 'missing' => int]

        foreach ($cards as $card) {
            $matrix  = $card->card_numbers ?? [];
            $missing = 0;

            foreach ($patternPositions as $cell) {
                if ($cell['free']) continue;

                $num = $matrix[$cell['col']][$cell['row']] ?? null;

                if ($num === null || !isset($drawnSet[$num])) {
                    $missing++;
                }
            }

            $userId = $card->roomUser?->user_id ?? $card->creator_user_id;
            if (!$userId) continue;

            $userId   = (int) $userId;
            $rawName  = $card->roomUser?->user?->name ?? '';
            $userName = $rawName !== '' ? $this->maskName($rawName) : ('Jugador #' . substr((string) $userId, -4));

            if (!isset($playerBest[$userId]) || $missing < $playerBest[$userId]['missing']) {
                $playerBest[$userId] = ['user_name' => $userName, 'missing' => $missing];
            }
        }

        if (empty($playerBest)) {
            return;
        }

        $players = [];
        foreach ($playerBest as $userId => $info) {
            $players[] = [
                'user_id'   => $userId,
                'user_name' => $info['user_name'],
                'missing'   => $info['missing'],
            ];
        }

        // Ordenar por menor cantidad de fichas faltantes (más cerca de ganar primero)
        usort($players, fn($a, $b) => $a['missing'] <=> $b['missing']);

        broadcast(new \App\Domains\Games\Bingo\Events\BingoPlayersProgress((int) $room->id, $players));
    }

    private function detectWinnerAndEnd(
        BingoRoom    $room,
        BingoRound   $round,
        BingoPattern $pattern,
        array        $patternPositions,
        array        $drawnNumbers,
        int          $lastDrawId,
        string       $variant = BingoVariant::BINGO_75
    ): ?array {
        // Salida rápida: si el patrón no tiene posiciones definidas no hay bingo posible
        if (empty($patternPositions)) {
            return null;
        }

        $drawnSet = array_flip($drawnNumbers);

        $cards = $this->activeCards($room, ['roomUser.user', 'cardLot']);

        $winners = [];

        foreach ($cards as $card) {
            $matrix = $card->card_numbers ?? [];

            $hasBingo = true;
            foreach ($patternPositions as $cell) {
                // Celda libre del cartón: siempre cuenta como completada
                if ($cell['free']) continue;

                $num = $matrix[$cell['col']][$cell['row']] ?? null;

                if ($num === null || !isset($drawnSet[$num])) {
                    $hasBingo = false;
                    break;
                }
            }

            if (!$hasBingo) continue;

            $winnerData = $this->claimCardWin($room, $round, $pattern, $card->id, $lastDrawId);
            if ($winnerData !== null) {
                $winners[] = $winnerData;
            }
        }

        if (empty($winners)) {
            return null;
        }

        // Cerrar la ronda y la sala (si es la ronda final)
        $this->closeRound($round);
        $this->closeRoomIfFinalRound($room, $round);

        // Evento de fin de juego para toda la sala
        broadcast(new BingoGameEnded(
            roomId:     (int) $room->id,
            drawId:     $lastDrawId,
            hasWinner:  true,
            totalDrawn: count($drawnNumbers),
            winners:    $winners,
            prize:      $winners[0]['prize'],
            endTime:    now()->toISOString(),
        ));

        $this->auditLogger->log($room, BingoRoomAuditLogger::EVENT_WINNER_DECLARED, 'system', 'Ganador(es) declarado(s)', [
            'round_id'      => (string) $round->id,
            'total_drawn'   => count($drawnNumbers),
            'winner_count'  => count($winners),
            'prize'         => $winners[0]['prize'] ?? 0,
        ]);
        $this->auditLogger->log($room, BingoRoomAuditLogger::EVENT_DRAW_FINISHED, 'system', 'Sorteo finalizado con ganador', ['total_drawn' => count($drawnNumbers)]);

        Log::info('[BingoDrawService] Ganador(es) — sorteo detenido', [
            'room_id'    => $room->id,
            'round_id'   => (string) $round->id,
            'round'      => $round->round,
            'pattern'    => $pattern->name,
            'drawn'      => count($drawnNumbers),
            'winners'    => count($winners),
        ]);

        return [
            'success'     => true,
            'room_id'     => $room->id,
            'total_chips' => count($drawnNumbers),
            'winners'     => $winners,
        ];
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
        BingoRoom    $room,
        BingoRound   $round,
        BingoPattern $pattern,
        string       $cardId,
        int          $lastDrawId
    ): ?array {
        $roundId   = (string) $round->id;
        $cardPrize = null;

        /** @var array{winnerData: array<string,mixed>, broadcast: array<string,mixed>}|null $txResult */
        $txResult = DB::transaction(function () use ($room, $round, $roundId, $pattern, $cardId, $lastDrawId, &$cardPrize) {
            $card = BingoCard::where('id', $cardId)
                ->lockForUpdate()
                ->with(['roomUser.user', 'cardLot'])
                ->first();

            if (!$card || (int) $card->status !== BingoCard::STATUS_ACTIVE) {
                // Ya fue reclamado por otro proceso (sorteo automático vs. check manual) o no existe.
                return null;
            }

            $card->update(['status' => BingoCard::STATUS_WINNER]);
            $this->invalidateActiveCardsCache();

            $userId   = $card->roomUser?->user_id ?? $card->creator_user_id;
            $user     = $card->roomUser?->user ?? User::find($userId);
            $userName = $user?->name ?? ('Jugador #' . $userId);

            $prizeAmounts = $this->resolvePrizeAmounts($room, $round, $card);
            $prize        = $prizeAmounts['usd_amount'];

            // prize_type viene del enum canónico de la ronda (PCT/FIXED/OTHER),
            // no del nombre del patrón (string mutable que puede renombrarse).
            $prizeType = match ($round->prize_type) {
                'FIXED' => 'full_house',
                'PCT'   => 'serial',
                'OTHER' => 'serial',
                default => 'serial',
            };

            $cardPrize = BingoCardPrize::create([
                'room_id'           => (string) $room->id,
                'creator_user_id'   => (string) ($userId ?? $card->creator_user_id),
                'seller_user_id'    => $card->seller_user_id ? (string) $card->seller_user_id : null,
                'seller_type'       => $card->seller_type,
                'room_user_id'      => (string) $card->room_user_id,
                'card_lot_id'       => (string) $card->card_lot_id,
                'winning_card_id'   => (string) $card->id,
                'currency_id'       => (string) $card->currency_id,
                'round_id'          => $roundId,
                'status'            => 'ACTIVE',
                'prize_type'        => $prizeType,
                'winning_pattern'   => $pattern->name,
                'usd_amount'        => $prizeAmounts['usd_amount'],
                'local_amount'      => $prizeAmounts['local_amount'],
                'prize_status'      => 'waiting',
                'verification_code' => strtoupper(bin2hex(random_bytes(8))),
            ]);

            Log::info('[BingoDrawService] Premio registrado en bingo_card_prizes', [
                'card_prize_id' => $cardPrize->id,
                'card_id'       => $card->id,
                'round_id'      => $roundId,
                'round_number'  => $round->round,
                'pattern'       => $pattern->name,
                'prize_type'    => $prizeType,
                'user_id'       => $userId,
            ]);

            return [
                'winnerData' => [
                    'user_id'       => (int) $userId,
                    'user_name'     => $userName,
                    'card_id'       => (string) $card->id,
                    'card_serial'   => $card->serial_number,
                    'card_prize_id' => (string) $cardPrize->id,
                    'prize'         => $prize,
                    'pattern'       => $pattern->name,
                    'round'         => $round->round,
                ],
                // Payload para broadcast — capturado aquí mientras los IDs están en scope.
                'broadcast' => [
                    'roomId'    => (int) $room->id,
                    'drawId'    => $lastDrawId,
                    'winnerId'  => (int) $userId,
                    'winnerName'=> $userName,
                    'cardId'    => (int) $card->id,
                    'pattern'   => $pattern->name,
                    'prize'     => $prize,
                ],
            ];
        });

        // Broadcast y pago FUERA de la transacción: la fila bingo_card_prizes ya está commitada.
        if ($txResult !== null && $cardPrize !== null) {
            $winnerData      = $txResult['winnerData'];
            $broadcastWinner = $txResult['broadcast'];

            broadcast(new BingoWinnerDeclared(
                roomId:     $broadcastWinner['roomId'],
                drawId:     $broadcastWinner['drawId'],
                winnerId:   $broadcastWinner['winnerId'],
                winnerName: $broadcastWinner['winnerName'],
                cardId:     $broadcastWinner['cardId'],
                pattern:    $broadcastWinner['pattern'],
                prize:      $broadcastWinner['prize'],
            ));

            $cardPrize->loadMissing('currency');
            $this->winEventService->reportMoney(
                userId: $broadcastWinner['winnerId'] ? (string) $broadcastWinner['winnerId'] : null,
                winnerNameOverride: $broadcastWinner['winnerId'] ? null : $broadcastWinner['winnerName'],
                gameSlug: 'bingo',
                gameName: 'Bingo',
                amountUsd: $broadcastWinner['prize'],
                amountLocal: (float) $cardPrize->local_amount,
                currencyId: $cardPrize->currency_id,
                sourceReference: (string) $cardPrize->id,
                metadata: ['room_id' => (string) $cardPrize->room_id, 'pattern' => $broadcastWinner['pattern']],
            );

            $this->prizePayoutService->payout($cardPrize);
        } else {
            $winnerData = null;
        }

        return $winnerData;
    }

    /**
     * Marca la ronda como FINISHED y registra la última ficha.
     */
    private function closeRound(BingoRound $round): void
    {
        $round->update([
            'status'    => 'FINISHED',
            'last_chip' => BingoDraw::where('room_id', (string) $round->room_id)
                ->orderBy('created_at', 'desc')
                ->value('chip'),
        ]);

        Log::info('[BingoDrawService] Ronda cerrada', [
            'round_id' => $round->id,
            'round'    => $round->round,
        ]);
    }

    /**
     * Si la ronda que acaba de terminar es la última configurada para la sala,
     * cambia el status de la sala a FINISHED y registra finished_at.
     */
    private function closeRoomIfFinalRound(BingoRoom $room, BingoRound $round): void
    {
        // Obtener número total de rondas desde la configuración de la sala
        $config = $room->relationLoaded('config') ? $room->config : $room->load('config')->config;
        $totalRounds = (int) ($config?->rounds ?? 1);

        // Contar cuántas rondas están ya FINISHED (incluyendo la que acaba de cerrarse)
        $finishedRounds = BingoRound::where('room_id', (string) $room->id)
            ->where('status', 'FINISHED')
            ->count();

        Log::info('[BingoDrawService] Evaluando cierre de sala', [
            'room_id'        => $room->id,
            'round_finished' => $round->round,
            'total_rounds'   => $totalRounds,
            'finished_count' => $finishedRounds,
        ]);

        if ($finishedRounds < $totalRounds) {
            // Quedan rondas pendientes — no cerrar la sala todavía
            return;
        }

        // Es la ronda final: cambiar status de la sala a FINISHED
        $finishedId = GameStatus::codeToId('FINISHED');

        if (!$finishedId) {
            Log::warning('[BingoDrawService] No existe GameStatus con code=FINISHED — sala no cerrada automáticamente');
            return;
        }

        $room->update([
            'status_id'   => $finishedId,
            'finished_at' => now(),
        ]);

        Log::info('[BingoDrawService] Sala cerrada (FINISHED)', [
            'room_id'     => $room->id,
            'status_id'   => $finishedId,
            'finished_at' => now()->toISOString(),
        ]);
    }

    /**
     * Obtiene la tasa de cambio activa más reciente para una moneda.
     * Cacheada en memoria por moneda durante la corrida actual del servicio:
     * un sorteo puede tener decenas de cartones ganadores y todos comparten
     * la misma tasa vigente en ese instante.
     */
    private function getActiveRate(string $currencyId): float
    {
        if (array_key_exists($currencyId, $this->rateCache)) {
            return $this->rateCache[$currencyId];
        }

        return $this->rateCache[$currencyId] = (float) ExchangeRate::where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderBy('created_at', 'desc')
            ->value('amount');
    }

    /**
     * Convierte un monto USD a moneda local usando el exchange rate de la moneda del cartón.
     * Si no hay tasa o la moneda es USD, devuelve el monto original.
     */
    private function convertToLocal(float $usdAmount, string $currencyId): float
    {
        $rate = $this->getActiveRate($currencyId);

        if ($rate <= 0) {
            return $usdAmount;
        }

        return round($usdAmount * $rate, 4);
    }

    /**
     * Resuelve el monto del premio (USD y moneda local) para un cartón ganador.
     *
     * Si la ronda es de tipo FIXED y existe un valor configurado por moneda
     * (`bingo_round_prizes`) para la moneda del cartón, se usa ese valor
     * (en moneda local), derivando el equivalente en USD mediante la tasa activa.
     * En cualquier otro caso, se usa el bote de la sala (`prize_pool`, en USD).
     */
    private function maskName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $first = $parts[0] ?? '';
        $visible = mb_substr($first, 0, 4);
        return $visible . str_repeat('*', max(0, mb_strlen($first) - 4))
            . (isset($parts[1]) ? ' ' . mb_substr($parts[1], 0, 1) . '.' : '');
    }

    private function resolvePrizeAmounts(BingoRoom $room, BingoRound $round, BingoCard $card): array
    {
        if ($round->prize_type === 'FIXED') {
            if (!$round->relationLoaded('prizes')) {
                $round->load('prizes');
            }

            $fixedPrize = $round->prizes->firstWhere('currency_id', (string) $card->currency_id);

            if ($fixedPrize) {
                $localAmount = (float) $fixedPrize->prize_value;
                $rate = $this->getActiveRate((string) $card->currency_id);
                $usdAmount = $rate > 0 ? round($localAmount / $rate, 4) : $localAmount;

                return ['usd_amount' => $usdAmount, 'local_amount' => $localAmount];
            }

            // Sin precio configurado para la moneda del cartón — sin pago monetario
            Log::warning('[BingoDrawService] Ronda FIXED sin precio para la moneda del cartón ganador', [
                'round_id'    => $round->id,
                'room_id'     => $room->id,
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
            if (!$round->relationLoaded('prizes')) {
                $round->load('prizes');
            }
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
                    'round_id'     => $round->id,
                    'room_id'      => $room->id,
                    'fallback_pct' => $pct,
                ]);
            }
            $usdAmount = round($totalUsd * ($pct / 100.0), 4);
        } else {
            $totalRounds = max(1, (int) ($room->config?->rounds ?? 1));
            $usdAmount   = round($totalUsd / $totalRounds, 4);
        }

        return ['usd_amount' => $usdAmount, 'local_amount' => $this->convertToLocal($usdAmount, (string) $card->currency_id)];
    }
}
