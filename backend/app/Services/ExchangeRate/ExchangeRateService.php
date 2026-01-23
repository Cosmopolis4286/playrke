<?php

namespace App\Services\ExchangeRate;

use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
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
class ExchangeRateService extends BaseService
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
     * Resuelve el ID del usuario basado en la autenticación o token proporcionado.
     *
     * @param string|null $token Token público opcional para identificar usuario.
     * @return int ID del usuario autenticado o derivado del token.
     *
     * @throws \DomainException Cuando no se puede resolver un ID válido de usuario.
     */
    private function resolveUserId(?string $token = null): int
    {
        if (Auth::check()) {
            return Auth::id();
        }

        if ($token) {
            return (int) $token;
        }

        throw new \DomainException('Usuario inválido o no identificado.');
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
     *
     * @throws \DomainException Si el userId no es válido o no se puede resolver.
     */
    public function create(ExchangeRateDTO $dto): void
    {
        $userId = $this->resolveUserId($dto->tokenId);
        $this->crud->create($dto, $userId);
    }

    /**
     * Obtener tasa de cambio para visualización.
     * @param string $exchangeRateId
     * @return array
     * @throws \DomainException Si el userId no es válido o no se puede resolver.
     */
    public function getByExchangeRate(string $exchangeRateId): ?Model
    {
        $userId = $this->resolveUserId();
        return $this->crud->show($exchangeRateId, $userId);
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
