<?php

namespace App\Services\ExchangeRate;

use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\DTOs\ExchangeRate\ExchangeRateContextDTO;
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
class ExchangeRateService
{
    //* Contexto al que aplica la tasa*/
    protected $role;

    public function __construct(
        protected ExchangeRateContext $context,
        protected ExchangeRateCrud $crud,
        protected ExchangeRateUpdater $updater,
    ) {
        $this->role = 'commerce';
    }

    /**
     * Obtener contexto para visualización de tasas.
     */
    public function context(Request $request): array
    {
        return $this->context->resolve($request)->toView();
    }

    /**
     * Crear una nueva tasa de cambio.
     */
    public function create(ExchangeRateDTO $dto): void
    {
        dd($dto, $this->role);
        $this->crud->create($dto, $this->role);
    }

    /**
     * Actualizar tasas desde proveedor externo.
     */
    public function updateRates(?int $userId, bool $check = false): bool
    {
        return $this->updater->updateRates($userId, $check);
    }
}
