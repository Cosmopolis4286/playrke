<?php

namespace App\Domain\ExchangeRate\Status;

use InvalidArgumentException;

/**
 * Servicio de dominio responsable de validar y
 * resolver transiciones de estado de las tasas de cambio.
 *
 * No conoce persistencia, modelos ni framework.
 */
final class ExchangeRateStatus
{
    /**
     * Estados permitidos por defecto.
     *
     * @var array<int, string>
     */
    private const ALLOWED_STATUSES = [
        'ACTIVE',
        'INACTIVE',
        'BLOCKED',
        'DELETED',
    ];

    /**
     * Alterna entre estados ACTIVE e INACTIVE.
     *
     * @param string $currentStatus Estado actual
     * @return string Nuevo estado
     *
     * @throws InvalidArgumentException Si el estado actual no es válido
     */
    public function toggle(string $currentStatus): string
    {
        $this->assertValid($currentStatus);

        return $currentStatus === 'ACTIVE'
            ? 'INACTIVE'
            : 'ACTIVE';
    }

    /**
     * Valida si un estado es permitido.
     *
     * @param string $status Estado a validar
     * @return void
     *
     * @throws InvalidArgumentException Si el estado no es válido
     */
    public function assertValid(string $status): void
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException("Estado inválido: {$status}");
        }
    }
}
