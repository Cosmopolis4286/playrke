<?php

namespace App\Enums\Games\Common;

/**
 * Tipos de señal sospechosa detectados por el sistema anti-cheat, compartidos
 * entre todos los juegos de la plataforma.
 */
enum AntiCheatSignalType: string
{
    // Trivia
    case INSTANT_RESPONSE            = 'INSTANT_RESPONSE';
    case CLIENT_SERVER_TIME_MISMATCH = 'CLIENT_SERVER_TIME_MISMATCH';
    case MISSING_CLIENT_TIMESTAMP    = 'MISSING_CLIENT_TIMESTAMP';

    // Bingo
    case IMPOSSIBLE_CLAIM_TIMING = 'IMPOSSIBLE_CLAIM_TIMING';
    case PATTERNED_CLAIM_LATENCY = 'PATTERNED_CLAIM_LATENCY';

    // Genérico / extensible a futuros juegos
    case RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';
    case OTHER                = 'OTHER';

    /**
     * Retorna una etiqueta legible para UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::INSTANT_RESPONSE            => 'Respuesta instantánea sospechosa',
            self::CLIENT_SERVER_TIME_MISMATCH => 'Desfase cliente/servidor sospechoso',
            self::MISSING_CLIENT_TIMESTAMP    => 'Timestamp de cliente ausente',
            self::IMPOSSIBLE_CLAIM_TIMING     => 'Reclamo de bingo con timing imposible',
            self::PATTERNED_CLAIM_LATENCY     => 'Patrón de latencia repetido en reclamos',
            self::RATE_LIMIT_EXCEEDED         => 'Límite de velocidad excedido',
            self::OTHER                       => 'Otro',
        };
    }

    /**
     * Severidad por defecto usada como peso en el conteo de reincidencia
     * de AntiCheatService::recentSeverity().
     */
    public function defaultSeverity(): int
    {
        return match ($this) {
            self::IMPOSSIBLE_CLAIM_TIMING, self::INSTANT_RESPONSE => 3,
            self::CLIENT_SERVER_TIME_MISMATCH, self::PATTERNED_CLAIM_LATENCY => 2,
            self::MISSING_CLIENT_TIMESTAMP, self::RATE_LIMIT_EXCEEDED, self::OTHER => 1,
        };
    }
}
