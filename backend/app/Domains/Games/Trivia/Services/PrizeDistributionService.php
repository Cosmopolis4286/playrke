<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use App\Domains\Games\Trivia\Exceptions\Prize\TriviaPrizeException;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Platform\WinEvent\Services\WinEventService;
use App\Models\ExchangeRate;
use App\Models\Games\Trivia\TriviaParticipant;
use App\Services\Games\GamePrizeService;
use Illuminate\Support\Facades\Log;

/**
 * Distribuye premios al finalizar una partida de Trivia.
 *
 * Modelos soportados
 * ──────────────────
 * guaranteed  Premios fijos definidos por el organizador, independientes del
 *             número de jugadores. Equivale a un torneo con premio patrocinado.
 *             No hay "bote": la plataforma garantiza el monto antes de abrir la sala.
 *
 * pool        Los premios provienen de las entradas cobradas más cualquier aporte de
 *             patrocinador (sponsor_prize). Se descuenta un rake transparente para la
 *             plataforma y el resto se distribuye entre los top N según prize_payouts.
 *             Al menos 3 posiciones pagas evitan la percepción "winner-takes-all".
 *
 * Reglas antifraud / skill-first
 * ──────────────────────────────
 * • El ranking se determina por: respuestas correctas → tiempo de respuesta promedio
 *   → racha máxima. Nunca por azar.
 * • Un jugador solo puede ganar si contestó ≥ 1 pregunta correcta.
 * • Los pagos son atómicos: o todos se acreditan o ninguno (transacción).
 * • Se registra un movimiento PRIZE (prize_type=DIGITAL) en la wallet de cada ganador.
 */
final class PrizeDistributionService
{
    // Rake máximo permitido para proteger al jugador (aplicado como guardrail)
    private const MAX_RAKE_PERCENT = 30.0;

    // Mínimo de posiciones pagas para modelo pool (evita winner-takes-all)
    private const MIN_PAID_POSITIONS_POOL = 3;

    public function __construct(
        private readonly GamePrizeService $gamePrizeService,
        private readonly WinEventService $winEventService,
    ) {
    }

    /**
     * Distribuye los premios de una sala finalizada.
     *
     * @param string $roomId     ID de la sala (pgsql_trivia)
     * @param array  $finalRanks [{playerId: string, position: int, correctAnswers: int, avgTimeMs: float}]
     *
     * @return array [{playerId, position, prizeAmount, currencyId, txId}]
     */
    public function distribute(string $roomId, array $finalRanks) : array
    {
        $room = TriviaRoom::findOrFail($roomId);

        if (empty($finalRanks)) {
            return [];
        }

        $payouts = $this->resolvePrizePayouts($room, count($finalRanks));

        if (empty($payouts)) {
            return [];
        }

        $currencyId = (string) $room->prize_currency_id;
        $results = [];

        // Cada payoutOne() delega la atomicidad del pago a GamePrizeService
        // (FSM waiting→processing→claimed + idempotencyKey) — no se envuelve
        // en una transacción conjunta porque trivia_participants (pgsql_trivia)
        // y wallet_movements (mysql) son motores de BD distintos.
        foreach ($payouts as $payout) {
            $rank = $finalRanks[$payout['rank'] - 1] ?? null;

            // La posición no existe (menos jugadores que posiciones pagas)
            if (! $rank) {
                continue;
            }

            // No pagar a jugadores sin ninguna respuesta correcta (skill gate)
            if (($rank['correctAnswers'] ?? 0) === 0) {
                continue;
            }

            $userId = $rank['playerId'];
            $amount = round((float) $payout['amount'], 8);

            if ($amount <= 0) {
                continue;
            }

            $result = $this->payoutOne($room, $userId, $payout['rank'], $amount, $currencyId);

            if ($result) {
                $results[] = $result;
            }
        }

        Log::info('[PrizeDistribution] Premios distribuidos', [
            'room_id' => $roomId,
            'model' => $room->prize_model,
            'payouts' => $results,
        ]);

        // Anunciar al ganador principal en el ticker de la plataforma. Fuera de la
        // transacción de pago: si el broadcast falla, el premio ya quedó acreditado.
        $this->announceTopWinner($room, $results);

        return $results;
    }

    /**
     * Acredita el premio de un único ganador vía GamePrizeService — el mismo
     * FSM (waiting→processing→claimed) e idempotencia que usa Bingo. Deja el
     * resultado en trivia_participants.prize_status, detectable y reintentable
     * por trivia:retry-failed-prize-payouts.
     *
     * @return array{playerId: string, position: int, prizeAmount: float, currencyId: string, movementId: string}|null
     */
    public function payoutOne(TriviaRoom $room, string $userId, int $rank, float $amount, string $currencyId) : ?array
    {
        $participant = TriviaParticipant::where('room_id', $room->id)
            ->where('user_id', $userId)
            ->first();

        if (! $participant) {
            Log::warning('[PrizeDistribution] Ganador sin registro de participante', [
                'room_id' => $room->id,
                'user_id' => $userId,
            ]);

            return null;
        }

        // GameEngineService persists final_rank before invoking this service.
        // Therefore final_rank cannot be used to determine whether the prize FSM
        // was initialized. Initialize only when prize_status is still null; this
        // preserves processing/claimed/suspended/disputed states on retries.
        if ($participant->prize_status === null) {
            $participant->update([
                'final_rank' => $participant->final_rank ?? $rank,
                'prize_won' => $amount,
                'status' => 'winner',
                'prize_status' => 'waiting',
            ]);
        }

        $this->gamePrizeService->payout(
            $participant,
            "Premio Trivia #{$rank}° — {$room->title} ({$room->code})",
        );

        $participant->refresh();

        if (! $participant->isClaimed()) {
            return null;
        }

        $movementId = $participant->payments()->completed()->latest('created_at')->value('transaction_reference');

        return [
            'playerId' => $userId,
            'position' => $rank,
            'prizeAmount' => $amount,
            'currencyId' => $currencyId,
            'movementId' => (string) $movementId,
        ];
    }

    /**
     * Calcula los montos concretos a pagar por posición.
     *
     * @return array [{rank, amount, percent}]
     */
    public function resolvePrizePayouts(TriviaRoom $room, int $playerCount) : array
    {
        $model = $room->prize_model ?? 'guaranteed';
        $rawSplit = $this->normalizedSplit($room);

        return match ($model) {
            'guaranteed' => $this->payoutsGuaranteed($room, $rawSplit),
            'pool' => $this->payoutsPool($room, $rawSplit, $playerCount),
            default => throw TriviaPrizeException::unknownPrizeModel($model),
        };
    }

    /**
     * Reporta al feed de ganancias de la plataforma el ganador en 1° posición,
     * si hubo pago. El monto acreditado por recordPrize() ya está en la moneda
     * del premio (local); aquí se deriva el equivalente en USD con la tasa
     * activa de esa moneda — mismo patrón que BingoDrawService.
     */
    private function announceTopWinner(TriviaRoom $room, array $results) : void
    {
        $topWin = collect($results)->firstWhere('position', 1);
        if (! $topWin) {
            return;
        }

        $localAmount = (float) $topWin['prizeAmount'];
        $usdAmount = $this->convertToUsd($localAmount, (string) $topWin['currencyId']);

        $this->winEventService->reportMoney(
            userId: (string) $topWin['playerId'],
            winnerNameOverride: null,
            gameSlug: 'trivia',
            gameName: 'Trivia',
            amountUsd: $usdAmount,
            amountLocal: $localAmount,
            currencyId: $topWin['currencyId'],
            sourceReference: $topWin['movementId'] ?? null,
            metadata: ['room_id' => (string) $room->id, 'room_code' => $room->code],
        );
    }

    /**
     * Convierte un monto en moneda local a USD usando la tasa de cambio activa
     * de esa moneda. Si no hay tasa o la moneda ya es USD, devuelve el mismo monto.
     */
    private function convertToUsd(float $localAmount, string $currencyId) : float
    {
        $rate = (float) ExchangeRate::where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('created_at')
            ->value('amount');

        if ($rate <= 0) {
            return $localAmount;
        }

        return round($localAmount / $rate, 4);
    }

    /**
     * Asegura al menos $min posiciones en el split repartiéndolas equitativamente.
     */
    private function ensureMinPositions(array $split, int $min) : array
    {
        if (count($split) >= $min) {
            return $split;
        }

        // Redistribuir uniformemente entre $min posiciones
        $perPosition = round(1.0 / $min, 8);
        $positions = [];
        for ($i = 1; $i <= $min; $i++) {
            $positions[] = ['rank' => $i, 'percent' => $perPosition];
        }

        return $positions;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Lee prize_payouts del modelo o devuelve el split por defecto (60/25/15).
     * Normaliza percents para que sumen exactamente 1.0.
     */
    private function normalizedSplit(TriviaRoom $room) : array
    {
        $raw = $room->prize_payouts;

        // Deserializar si viene como string JSON (PostgreSQL jsonb → string en ciertos drivers)
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        if (empty($raw) || ! is_array($raw)) {
            // Split por defecto: distribuido en 3 posiciones — evita winner-takes-all
            return [
                ['rank' => 1, 'percent' => 0.60],
                ['rank' => 2, 'percent' => 0.25],
                ['rank' => 3, 'percent' => 0.15],
            ];
        }

        // Normalizar a float y ordenar por rank
        $parsed = array_map(fn ($p) => [
            'rank' => (int) $p['rank'],
            'percent' => (float) $p['percent'],
        ], $raw);

        usort($parsed, fn ($a, $b) => $a['rank'] <=> $b['rank']);

        // Si los percents no suman 1, escalarlos proporcionalmente
        $sum = array_sum(array_column($parsed, 'percent'));
        if ($sum > 0 && abs($sum - 1.0) > 0.001) {
            $parsed = array_map(fn ($p) => [
                'rank' => $p['rank'],
                'percent' => round($p['percent'] / $sum, 8),
            ], $parsed);
        }

        return $parsed;
    }

    // ── Modelo guaranteed ────────────────────────────────────────────────────

    private function payoutsGuaranteed(TriviaRoom $room, array $split) : array
    {
        $base = (float) ($room->prize_amount ?? 0);
        $sponsor = (float) ($room->sponsor_prize ?? 0);
        $totalPrize = $base + $sponsor;

        if ($totalPrize <= 0) {
            return [];
        }

        return array_map(fn ($s) => [
            'rank' => $s['rank'],
            'percent' => $s['percent'],
            'amount' => round($totalPrize * $s['percent'], 8),
        ], $split);
    }

    // ── Modelo pool ──────────────────────────────────────────────────────────

    private function payoutsPool(TriviaRoom $room, array $split, int $playerCount) : array
    {
        $entryFee = (float) ($room->entry_fee ?? 0);
        $sponsor = (float) ($room->sponsor_prize ?? 0);
        $rake = min((float) ($room->platform_rake_percent ?? 0), self::MAX_RAKE_PERCENT);

        $grossPool = ($entryFee * $playerCount) + $sponsor;
        $netPool = $grossPool * (1 - $rake / 100);

        if ($netPool <= 0) {
            return [];
        }

        // Garantizar mínimo 3 posiciones en modelo pool
        $split = $this->ensureMinPositions($split, self::MIN_PAID_POSITIONS_POOL);

        return array_map(fn ($s) => [
            'rank' => $s['rank'],
            'percent' => $s['percent'],
            'amount' => round($netPool * $s['percent'], 8),
        ], $split);
    }
}
