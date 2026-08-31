<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Enums;

/**
 * Tipos de power-up disponibles en una partida de Trivia.
 * Cada jugador recibe 1 uso de cada tipo por partida cuando la sala tiene
 * settings.allow_powerups = true (ver JoinRoomAction::defaultPowerUps()).
 */
enum TriviaPowerUpType: string
{
    case FIFTY_FIFTY    = 'fifty_fifty';
    case DOUBLE_POINTS  = 'double_points';
    case SKIP           = 'skip';
    case EXTRA_TIME     = 'extra_time';

    public function label(): string
    {
        return match ($this) {
            self::FIFTY_FIFTY   => '50/50',
            self::DOUBLE_POINTS => 'Doble puntos',
            self::SKIP          => 'Saltar pregunta',
            self::EXTRA_TIME    => 'Tiempo extra',
        };
    }

    /**
     * Uso inicial por jugador, por partida, cuando la sala habilita power-ups.
     *
     * @return array<string, int>
     */
    public static function defaultAllowance(): array
    {
        return array_fill_keys(array_map(fn (self $t) => $t->value, self::cases()), 1);
    }
}
