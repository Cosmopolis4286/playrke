<?php

namespace App\Services\ExchangeRate;

use App\Contracts\ExchangeRate\RoleExchangeRateRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Http\Resources\ExchangeRate\ExchangeRateResource;
use App\Services\Roles\RoleUserService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de aplicación responsable de operaciones CRUD
 * sobre tasas de cambio (ExchangeRate) según el rol activo del usuario.
 *
 * Orquesta la lógica para:
 * - Resolución de rol activo y entidad asociada
 * - Ejecución de operaciones CRUD delegadas a repositorios
 * - Aplicación de reglas de dominio específicas
 *
 * @final
 */
final class ExchangeRateCrudService
{
    /**
     * Constructor con inyección de dependencias.
     *
     * @param RoleExchangeRateRepositoryInterface $roleExchangeRate Repositorio roles en general
     * @param CommerceExchangeRateRepositoryInterface $commerce Repositorio para rol commerce
     * @param CustomerExchangeRateRepositoryInterface $customer Repositorio para rol customer
     * @param RoleUserService $roleUserService Servicio para resolución de roles y entidades de usuario
     * @param ExchangeRateDomainService $domain Servicio de lógica de dominio para tasas
     */
    public function __construct(
        private readonly RoleExchangeRateRepositoryInterface $roleExchangeRate,
        private readonly CommerceExchangeRateRepositoryInterface $commerce,
        private readonly CustomerExchangeRateRepositoryInterface $customer,
        private readonly RoleUserService $roleUserService,
        private readonly ExchangeRateDomainService $domain,
    ) {}

    /**
     * Crea o restaura una tasa de cambio para el rol activo.
     *
     * @param ExchangeRateDTO $dto DTO con datos de la tasa a crear
     * @param int $userId ID del usuario que realiza la operación
     *
     * @return bool Indica si la creación fue exitosa
     *
     * @throws DomainException Si no se puede resolver rol o entidad
     */
    public function create(ExchangeRateDTO $dto, int $userId): bool
    {
        $role = $this->resolveRole($userId);
        $roleEntityId = $this->resolveRoleEntityId($userId, $role);

        return DB::transaction(function () use ($dto, $role, $roleEntityId, $userId) {
            // Crea o restaura la tasa según la moneda y entidad
            $this->repo($role)->createOrRestoreByCurrency($roleEntityId, $dto);

            Log::info('ExchangeRate creado/restaurado', [
                'user_id'     => $userId,
                'role'        => $role,
                'currency_id' => $dto->currencyId,
            ]);

            return true;
        });
    }

    /**
     * Obtiene una tasa de cambio para visualización, con sus relaciones.
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @param int $userId ID del usuario que realiza la consulta
     *
     * @return ExchangeRateResource Recurso formateado para respuesta
     *
     * @throws DomainException Si la tasa no se encuentra o falla resolución de rol
     */
    public function show(string $exchangeRateId, int $userId): ExchangeRateResource
    {
        $role = $this->resolveRole($userId);

        $exchangeRate = $this->repo($role)->findWithRelations(
            ['currency:id,iso_code'],
            $exchangeRateId,
            ['id', 'currency_id', 'amount', 'status']
        );

        if (!$exchangeRate) {
            throw new DomainException("Tasa de cambio no encontrada: {$exchangeRateId}");
        }

        return new ExchangeRateResource($exchangeRate);
    }

    /**
     * Actualiza una tasa de cambio existente, calculando porcentaje de cambio.
     *
     * @param ExchangeRateDTO $dto DTO con datos actualizados, debe incluir ID
     * @param int $userId ID del usuario que realiza la actualización
     *
     * @return bool Indica si la actualización fue exitosa
     *
     * @throws DomainException Si no se proporciona ID o tasa no existe
     */
    public function update(ExchangeRateDTO $dto, int $userId): bool
    {
        throw_if(!$dto->id, DomainException::class, 'ID requerido');

        $repo = $this->repo($this->resolveRole($userId));

        return DB::transaction(function () use ($repo, $dto) {
            $oldAmount = $repo->getValueById($dto->id, 'amount');

            throw_if(is_null($oldAmount), DomainException::class, "Tasa con ID {$dto->id} no encontrada");

            $repo->update($dto->id, [
                ...$dto->toArray(),
                // Calcula y guarda porcentaje de variación respecto al valor anterior
                'percentage' => $this->domain->calculatePercentage($oldAmount, $dto->amount),
                // Restaura si estaba eliminado
                'deleted_at' => null,
            ]);

            Log::info('ExchangeRate actualizado', ['id' => $dto->id, 'new_amount' => $dto->amount]);

            return true;
        });
    }

    /**
     * Elimina una tasa de cambio según el rol activo del usuario.
     *
     * @param string $id ID de la tasa a eliminar
     * @param int $userId ID del usuario que solicita la eliminación
     *
     * @return bool Indica si la eliminación fue exitosa
     *
     * @throws DomainException Si la tasa no existe o falla resolución de rol
     */
    public function deleteByRole(string $id, int $userId): bool
    {
        $repo = $this->repo($this->resolveRole($userId));

        $exists = $repo->exists($id);
        throw_if(!$exists, DomainException::class, "Tasa de cambio no encontrada: {$id}");

        return $repo->delete($id);
    }

    /**
     * Alterna el estado activo/inactivo de una tasa según el rol del usuario.
     *
     * @param string $id ID de la tasa de cambio
     * @param int $userId ID del usuario que solicita el cambio de estado
     *
     * @return bool Indica si el cambio fue exitoso
     *
     * @throws DomainException Si la tasa no existe o falla resolución de rol
     */
    public function changeStatusByRole(string $id, int $userId): bool
    {
        $repo = $this->repo($this->resolveRole($userId));

        $exchangeRate = $repo->findOrFail($id);
        throw_if(!$exchangeRate, DomainException::class, "Tasa de cambio no encontrada: {$id}");

        return $repo->toggleStatus($exchangeRate);
    }

    /* =====================================================
     | Métodos privados para resolución de dependencias
     |=====================================================*/

    /**
     * Resuelve el rol activo del usuario.
     *
     * @param int $userId ID del usuario
     * @return string Prefijo del rol activo (ej. 'commerce', 'customer')
     *
     * @throws DomainException Si no se puede determinar el rol
     */
    private function resolveRole(int $userId): string
    {
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        if (!$role) {
            throw new DomainException("No se pudo resolver el rol del usuario {$userId}");
        }

        return $role;
    }

    /**
     * Resuelve el ID de la entidad asociada al rol activo.
     *
     * @param int $userId ID del usuario
     * @param string $role Prefijo del rol activo
     *
     * @return int ID de la entidad relacionada al rol
     *
     * @throws DomainException Si no se encuentra entidad asociada
     */
    private function resolveRoleEntityId(int $userId, string $role): int
    {
        $roleEntityId = $this->roleUserService->getUserRoleEntityId($userId, $role);

        if (!$roleEntityId) {
            throw new DomainException(
                "No se encontró entidad asociada al rol '{$role}' para el usuario {$userId}"
            );
        }

        return $roleEntityId;
    }

    /**
     * Obtiene el repositorio correspondiente según el rol.
     *
     * @param string $role Prefijo del rol activo
     *
     * @return CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface
     *
     * @throws DomainException Si el rol no es válido
     */
    private function repo(
        string $role
    ): CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface {
        return match ($role) {
            'commerce' => $this->commerce,
            'customer' => $this->customer,
            default    => throw new DomainException("Rol inválido: {$role}"),
        };
    }
}
