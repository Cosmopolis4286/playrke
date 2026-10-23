<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use App\Domains\Games\Trivia\Enums\TriviaPowerUpType;
use App\Domains\Games\Trivia\Exceptions\PowerUp\TriviaPowerUpException;
use App\Domains\Marketplace\Inventory\Exceptions\AssetNotAvailableException;
use App\Domains\Marketplace\Inventory\Services\AssetConsumptionService;
use App\Models\Games\Trivia\TriviaParticipant;
use Illuminate\Support\Facades\Redis;

/**
 * Consume power-ups de un participante y aplica su efecto para la pregunta
 * activa. Los power-ups viven en dos lugares distintos por diseño:
 *
 *  - trivia_participants.power_ups/power_ups_used (Postgres): cuántos le
 *    quedan a cada jugador — dato duradero, sobrevive toda la partida.
 *  - Redis (trivia:powerup:{gameId}:{playerId}:{questionId}): efecto
 *    ACTIVO para la pregunta actual (double_points, skip) — vive solo lo
 *    que dura esa pregunta, mismo patrón que el resto del estado en vivo
 *    del juego (RedisGameStateRepository).
 *
 * fifty_fifty no necesita estado en Redis: su efecto se resuelve y se
 * devuelve al cliente en el mismo request que lo activa.
 */
class TriviaPowerUpService
{
    // Bonus fijo de tiempo extra. Individual: solo se suma al calcular si
    // ESE jugador respondió a tiempo — el timer visual de los demás y el
    // job de avance automático (AdvanceTriviaQuestion) no se ven afectados.
    // Si el avance automático dispara antes de que el jugador aproveche el
    // extra, pierde la ventana igual que cualquiera — es una ventaja
    // marginal, no una garantía contra el cierre global de la pregunta.
    public const EXTRA_TIME_BONUS_MS = 5000;

    /**
     * Código de AssetDefinition consumido como fallback cuando se agota el
     * uso gratis por partida — ver AssetDefinitionSeeder. Un solo ticket
     * cubre cualquier tipo de power-up (no hay un ticket por tipo todavía;
     * diferenciarlos es una decisión de balance de juego para cuando existan
     * datos reales de uso, no algo a inventar ahora).
     */
    private const FALLBACK_ASSET_CODE = 'TRIVIA_HINT_TICKET';
    private const REDIS_TTL_SECONDS = 120; // dura más que el tiempo máximo de una pregunta

    public function __construct(
        private readonly AssetConsumptionService $assetConsumption,
    ) {
    }

    /**
     * Reserva atómicamente la activación de un power-up que deja un flag
     * por pregunta. Evita que requests repetidos consuman varios usos para
     * un único efecto. El valor pending no concede el efecto al jugador.
     */
    public function reserveActivation(string $gameId, string $userId, string $questionId, string $type): bool
    {
        $key = $this->key($gameId, $userId, $questionId, $type);

        $reserved = Redis::set($key, 'pending', 'EX', self::REDIS_TTL_SECONDS, 'NX');

        return $reserved !== false && $reserved !== null;
    }

    /** Libera una reserva si el consumo del power-up no pudo completarse. */
    public function releaseActivation(string $gameId, string $userId, string $questionId, string $type): void
    {
        Redis::del($this->key($gameId, $userId, $questionId, $type));
    }

    /**
     * Activa double_points para la próxima respuesta del jugador a esta
     * pregunta específica. TriviaAnswerController::store() lo consulta y
     * consume al calcular los puntos.
     */
    public function activateDoublePoints(string $gameId, string $userId, string $questionId) : void
    {
        Redis::setex($this->key($gameId, $userId, $questionId, 'double_points'), self::REDIS_TTL_SECONDS, '1');
    }

    /**
     * Activa el bonus de tiempo extra para ESTE jugador en esta pregunta.
     */
    public function activateExtraTime(string $gameId, string $userId, string $questionId) : void
    {
        Redis::setex($this->key($gameId, $userId, $questionId, 'extra_time'), self::REDIS_TTL_SECONDS, '1');
    }

    /**
     * Consume un power-up del participante: primero el uso gratis por
     * partida (power_ups_used, Postgres); si ya se agotó, intenta consumir
     * un PlayerAsset del Marketplace (TRIVIA_HINT_TICKET) como fallback —
     * ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 31. Lanza si ninguna de
     * las dos fuentes tiene usos disponibles — la Action/Controller que
     * llama decide el código HTTP.
     */
    public function consume(string $roomId, string $userId, TriviaPowerUpType $type) : TriviaParticipant
    {
        $participant = TriviaParticipant::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if (! $participant) {
            throw TriviaPowerUpException::notEnrolled();
        }

        if ($participant->usePowerUp($type->value)) {
            $participant->save();

            return $participant;
        }

        try {
            $this->assetConsumption->consumeByCode($userId, self::FALLBACK_ASSET_CODE, 1, 'TRIVIA');
        } catch (AssetNotAvailableException) {
            throw TriviaPowerUpException::noUsesAvailable();
        }

        // El fallback consumió el activo, pero power_ups_used no se
        // incrementa: ese contador es específico del uso "gratis" por
        // partida (ver getAvailablePowerUp()) y seguir sumándolo ahí
        // haría que un segundo uso gratis pareciera agotado sin haberlo
        // usado. El PlayerAsset consumido ya es el registro de auditoría
        // de este uso (ver InventoryService::consume()).
        return $participant;
    }

    /**
     * Consulta el flag de double_points sin consumirlo. La respuesta debe
     * quedar persistida antes de borrar el flag, para que un request duplicado
     * o un fallo de PostgreSQL no haga perder un power-up ya pagado.
     */
    public function hasDoublePointsFlag(string $gameId, string $userId, string $questionId) : bool
    {
        return Redis::get($this->key($gameId, $userId, $questionId, 'double_points')) === '1';
    }

    /**
     * Borra el flag después de que la respuesta y la puntuación se confirmen.
     */
    public function clearDoublePointsFlag(string $gameId, string $userId, string $questionId) : void
    {
        Redis::del($this->key($gameId, $userId, $questionId, 'double_points'));
    }

    /**
     * Compatibilidad para consumidores existentes: consulta y consume el flag.
     * El flujo de respuesta debe usar hasDoublePointsFlag() y clear...() tras
     * confirmar la transacción.
     */
    public function consumeDoublePointsFlag(string $gameId, string $userId, string $questionId) : bool
    {
        $active = $this->hasDoublePointsFlag($gameId, $userId, $questionId);

        if ($active) {
            $this->clearDoublePointsFlag($gameId, $userId, $questionId);
        }

        return $active;
    }

    /**
     * Bonus de milisegundos a sumar al límite de tiempo de ESTE jugador para
     * esta pregunta, si activó tiempo extra (0 si no). No se consume aquí —
     * TriviaAnswerController lo usa solo para el cálculo, no como flag de
     * un solo uso (el jugador ya pagó el uso al activarlo, ver consume()).
     */
    public function extraTimeBonusMs(string $gameId, string $userId, string $questionId) : int
    {
        return Redis::get($this->key($gameId, $userId, $questionId, 'extra_time')) === '1'
            ? self::EXTRA_TIME_BONUS_MS
            : 0;
    }

    /**
     * Resuelve el efecto de 50/50 para una pregunta: 2 índices de opciones
     * INCORRECTAS a ocultar en el cliente. Se calcula aquí (server-side) y
     * nunca en el frontend, para no exponer cuál es la respuesta correcta
     * inspeccionando la red — igual criterio que el resto del anti-cheat
     * de Trivia (tiempos de respuesta server-side).
     *
     * @param int $correctIndex índice de la opción correcta (0-3)
     * @param int $optionCount  cantidad total de opciones de la pregunta
     *
     * @return int[] hasta 2 índices de opciones incorrectas a ocultar
     */
    public function resolveFiftyFifty(int $correctIndex, int $optionCount) : array
    {
        $incorrectIndexes = array_values(array_filter(
            range(0, $optionCount - 1),
            fn (int $i) => $i !== $correctIndex,
        ));

        shuffle($incorrectIndexes);

        return array_slice($incorrectIndexes, 0, min(2, count($incorrectIndexes)));
    }

    private function key(string $gameId, string $userId, string $questionId, string $type) : string
    {
        return "trivia:powerup:{$gameId}:{$userId}:{$questionId}:{$type}";
    }
}
