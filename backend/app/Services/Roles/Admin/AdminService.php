<?php

namespace App\Services\Roles\Admin;

/**
 * Servicio de dominio para gestión del administrador
 *
 * Responsabilidades:
 * - Exponer casos de uso del dominio
 * - Orquestar servicios especializados
 * - Mantener a los controllers desacoplados
 *
 * NO contiene lógica HTTP ni infraestructura.
 */
class AdminService
{
    public function __construct(
        protected AdminContextService $context,
    ) {}

    /**
     * Obtener contexto para visualización de vidtas.
     */
    public function context(string $context): array
    {
        return $this->context->resolve($context)->toView();
    }
}
