<?php

namespace App\Domains\Games\Bingo\ValueObjects;

/**
 * Reglas que relacionan el tipo de bote (PrizePoolType) con las rondas.
 *
 * Tipos de ronda (roundsConfig[*].prizeType en el request / bingo_rounds.prize_type):
 *  - percentage (PCT)  → paga un % del bote en dinero de la sala (prize_pool).
 *  - fixed      (FIXED)→ paga un monto fijo por moneda (no sale del bote).
 *  - prize      (OTHER)→ premio físico / servicio del catálogo, sin dinero.
 *
 * Bote Premio/Servicio (PRIZE):
 *  - Debe tener al menos una ronda de premio físico (si no, no es "de premio").
 *  - PURO (sin rondas %): la sala no acumula bote en dinero — nada lo
 *    consumiría. El neto de cada venta (tras RKE, creador, vendedor y jackpot)
 *    se registra en BingoCreatorSale.prize_pool_amount como fondos del premio,
 *    que pone el creador.
 *  - HÍBRIDO (con alguna ronda %): acumula bote como cualquier sala, para
 *    pagar esas rondas; las rondas de premio físico conviven con ellas.
 *
 * Resto de tipos (ACCUMULATIVE/FIXED/MIXED): si TODAS las rondas son de
 * premio físico el bote en dinero se acumularía sin repartirse nunca, así que
 * se exige usar el tipo Premio/Servicio.
 */
final class BingoRoomPrizeRules
{
    public const POOL_ACCUMULATIVE = 'ACCUMULATIVE'; // = PrizePoolType::CODE_ACCUMULATIVE
    public const POOL_PRIZE = 'PRIZE';
    public const ROUND_FIXED = 'fixed';

    public const ROUND_PERCENTAGE = 'percentage';
    public const ROUND_PRIZE = 'prize';

    /**
     * ¿La sala acumula bote en dinero con sus ventas? Todas salvo PRIZE puro.
     */
    public static function accruesMoneyPool(?string $poolCode, array $roundTypes) : bool
    {
        return $poolCode !== self::POOL_PRIZE || self::usesMoneyPool($roundTypes);
    }

    /**
     * Error de coherencia tipo de bote ↔ rondas, o null.
     */
    public static function roundsError(?string $poolCode, array $roundTypes) : ?string
    {
        if ($poolCode === null || $roundTypes === []) {
            return null;
        }

        $hasPrizeRound = in_array(self::ROUND_PRIZE, $roundTypes, true);
        $allPrizeRounds = ! array_diff($roundTypes, [self::ROUND_PRIZE]);

        if ($poolCode === self::POOL_PRIZE && ! $hasPrizeRound) {
            return 'Un bote Premio/Servicio necesita al menos una ronda de premio físico (P/S).';
        }
        if ($poolCode !== self::POOL_PRIZE && $allPrizeRounds) {
            return 'Si todas las rondas son de premio físico (P/S), el tipo de bote debe ser Premio/Servicio: si no, el bote en dinero nunca se repartiría.';
        }

        return null;
    }

    /** Tipos de ronda del request, normalizados desde roundsConfig. */
    public static function roundTypes(mixed $roundsConfig) : array
    {
        if (! is_array($roundsConfig)) {
            return [];
        }

        return array_values(array_map(
            fn ($r) => is_array($r) ? ($r['prizeType'] ?? self::ROUND_PERCENTAGE) : self::ROUND_PERCENTAGE,
            $roundsConfig,
        ));
    }

    /**
     * ¿Aplica el "Valor Fijo/Extra" (monto inicial del bote)? No en el
     * acumulativo, ni en PRIZE puro (no hay bote en dinero que sembrar).
     */
    public static function usesInitialAmount(?string $poolCode, array $roundTypes) : bool
    {
        if ($poolCode === null || $poolCode === self::POOL_ACCUMULATIVE) {
            return false;
        }

        return self::accruesMoneyPool($poolCode, $roundTypes);
    }

    /** ¿Alguna ronda consume el bote en dinero de la sala? (solo las de %). */
    public static function usesMoneyPool(array $roundTypes) : bool
    {
        return in_array(self::ROUND_PERCENTAGE, $roundTypes, true);
    }
}
