<?php

namespace App\Services\Roles\Admin;

use App\DTOs\Roles\Admin\AdminDTO;
use App\DTOs\Roles\Admin\AdminContextDTO;
use Illuminate\Http\Request;

/**
 * Servicio de dominio para gestión de tasas de cambio.
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
        // protected ExchangeRateCrud $crud,
        // protected ExchangeRateUpdater $updater,
    ) {}

    /**
     * Obtener contexto para visualización de tasas.
     */
    public function context(string $context): AdminContextDTO
    {
        return $this->context->resolve($context);
    }

    /**
     * Crear una nueva tasa de cambio.
     */
    // public function create(AdminDTO $dto): void
    // {
    //     $this->crud->create($dto);
    // }

    /**
 * Actualizar tasas desde proveedor externo.
 */
    // public function updateRates(?int $userId, bool $check = false): bool
    // {
    //     return $this->updater->updateRates($userId, $check);
    // }
}
