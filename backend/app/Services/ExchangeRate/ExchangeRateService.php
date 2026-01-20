<?php

namespace App\Services\ExchangeRate;

use App\DTOs\ExchangeRate\ExchangeRateDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    /**
     * Contexto al que aplica la tasa (prefijo de rol o token público).
     *
     * @var string|null
     */
    protected ?string $role = null;

    /**
     * Constructor del servicio.
     *
     * @param ExchangeRateContext $context
     * @param ExchangeRateCrud $crud
     * @param ExchangeRateUpdater $updater
     */
    public function __construct(
        protected ExchangeRateContext $context,
        protected ExchangeRateCrud $crud,
        protected ExchangeRateUpdater $updater,
    ) {
        // No asignar $role aquí, se resuelve dinámicamente en cada operación
    }

    /**
     * Resolver contexto de usuario según autenticación o token.
     *
     * @param string|null $token
     * @return string|null
     */
    private function resolveRole(?string $token = null): ?string
    {
        if (Auth::check()) {
            // Retornar el primer prefijo del rol asignado al usuario autenticado
            return Auth::user()->roles()->pluck('prefix')->first();
        }

        return $token;
    }

    /**
     * Obtener contexto para visualización de tasas.
     *
     * @param Request $request
     * @return array
     */
    public function context(Request $request): array
    {
        return $this->context->resolve($request)->toView();
    }

    /**
     * Crear una nueva tasa de cambio.
     *
     * @param ExchangeRateDTO $dto
     * @return void
     */
    public function create(ExchangeRateDTO $dto): void
    {
        $role = $this->resolveRole($dto->token ?? null);
        $this->crud->create($dto, $role);
    }

    /**
     * Actualizar tasas desde proveedor externo.
     *
     * @param int|null $userId
     * @param bool $check
     * @return bool
     */
    public function updateRates(?int $userId = null, bool $check = false): bool
    {
        return $this->updater->updateRates($userId, $check);
    }
}
