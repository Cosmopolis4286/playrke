<?php

namespace App\Domains\Common\Status;

use InvalidArgumentException;

/**
 * Servicio de dominio responsable de validar estados
 * y resolver transiciones simples de estado.
 *
 * No conoce persistencia, modelos ni framework.
 */
final class StatusChanger
{
    /**
     * Estados válidos del dominio.
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
     * Estados que permiten transición toggle.
     *
     * @var array<int, string>
     */
    private const TOGGLEABLE_STATUSES = [
        'ACTIVE',
        'INACTIVE',
    ];

    /**
     * Valida si un estado pertenece al dominio.
     *
     * @param string $status Estado a validar
     *
     * @throws InvalidArgumentException Si el estado no es válido
     */
    public function assertValid(string $status) : void
    {
        if (! in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException(
                "Estado inválido: '{$status}'",
            );
        }
    }

    /**
     * Alterna entre estados ACTIVE e INACTIVE.
     *
     * BLOCKED y DELETED no pueden alternarse
     * automáticamente por reglas de dominio.
     *
     * @param string $currentStatus Estado actual
     *
     * @return string Nuevo estado
     *
     * @throws InvalidArgumentException Si el estado no es válido
     * @throws InvalidArgumentException Si la transición no está permitida
     */
    public function toggle(string $currentStatus) : string
    {
        $this->assertValid($currentStatus);
        $this->assertToggleable($currentStatus);

        return $currentStatus === 'ACTIVE'
            ? 'INACTIVE'
            : 'ACTIVE';
    }

    /**
     * Valida si un estado permite transición toggle.
     *
     * @param string $status Estado actual
     *
     * @throws InvalidArgumentException Si la transición no está permitida
     */
    private function assertToggleable(string $status) : void
    {
        if (! in_array($status, self::TOGGLEABLE_STATUSES, true)) {
            throw new InvalidArgumentException(
                "El estado '{$status}' no permite alternancia",
            );
        }
    }
}
