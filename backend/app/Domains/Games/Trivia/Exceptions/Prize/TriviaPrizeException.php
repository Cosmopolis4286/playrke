<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Exceptions\Prize;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio relacionadas a la distribución de premios de
 * Trivia (PrizeDistributionService).
 *
 * IMPORTANTE:
 * - El dominio expone mensajes humanos directos (no ERROR_CODES) porque
 *   son específicos de este flujo y no se repiten en otro dominio.
 */
final class TriviaPrizeException extends DomainException
{
    /**
     * TriviaRoom::prize_model contiene un valor fuera del match exhaustivo
     * ('guaranteed'/'pool') — esto representa un dato corrupto o una
     * configuración mal cargada (error interno/de config), no una regla de
     * negocio violada por el usuario, de ahí el status 500.
     */
    public static function unknownPrizeModel(string $model) : self
    {
        return new self("Modelo de premio desconocido: {$model}", 500);
    }
}
