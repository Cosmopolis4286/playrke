<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Http\Resources\ExchangeRate\ExchangeRateResource;
use App\Services\Roles\RoleUserService;
use DomainException;
use Illuminate\Support\Facades\DB;

class ExchangeRateCrudService
{
    public function __construct(
        protected CommerceExchangeRateRepositoryInterface $commerce,
        protected CustomerExchangeRateRepositoryInterface $customer,
        protected RoleUserService $roleUserService
    ) {}

    /**
     * Crea un nuevo tipo de cambio para un rol específico.
     *
     * @param ExchangeRateDTO $dto Datos para crear la tasa de cambio.
     * @param int $userId ID del usuario.
     * @return bool True si la transacción fue exitosa, false en caso de error.
     *
     * @throws \DomainException Si el rol no es válido o no identificado.
     */
    public function create(ExchangeRateDTO $dto, int $userId): bool
    {
        try {
            $rolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

            $roleForeignKey = "{$rolePrefix}_id";

            $rolePrefixID = $this->roleUserService->getUserRoleEntityId($userId, $rolePrefix);

            if (!$rolePrefixID) {
                throw new \DomainException("No se encontró entidad asociada al rol '{$rolePrefix}' para el usuario: {$userId}");
            }

            $data = $dto->toArray() + [$roleForeignKey => $rolePrefixID];

            DB::transaction(function () use ($rolePrefix, $data) {
                $this->repo($rolePrefix)->create($data);
            });

            return true;
        } catch (\Throwable $e) {
            // Aquí puedes hacer un log del error si quieres
            return false;
        }
    }

    /**
     * Obtener tasa de cambio para visualización.
     *
     * @param string $exchangeRateId
     * @param int $userId
     *
     * @return ExchangeRateResource
     */
    public function show(string $exchangeRateId, int $userId): ExchangeRateResource
    {
        $rolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

        $exchangeRate = $this->repo($rolePrefix)->findWithRelations(
            ['currency:id,iso_code'],
            $exchangeRateId,
            ['id', 'currency_id', 'amount', 'status']
        );

        return new ExchangeRateResource($exchangeRate);
    }

    /**
     * Actualiza un tipo de cambio existente.
     *
     * @param ExchangeRateDTO $dto Debe contener un ID válido.
     * @param string $userId ID de usuario
     * @return bool True si la transacción fue exitosa, false en caso de error.
     *
     * @throws \DomainException Si el ID no está definido o el ID de usuario no es inválido.
     */
    public function update(ExchangeRateDTO $dto, string $userId): bool
    {
        try {
            $rolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

            $exchangeRateId = $dto->id;
            throw_if(!$exchangeRateId, \DomainException::class, 'ID requerido para actualizar.');

            // Obtener el valor anterior del monto para calcular el porcentaje
            $oldAmount = $this->repo($rolePrefix)->getValueById($exchangeRateId, 'amount');
            if ($oldAmount == 0) {
                throw new \DomainException('El monto anterior no puede ser cero para calcular el porcentaje.');
            }

            // Calcular el porcentaje de cambio (nuevo / viejo * 100)
            if ($dto->amount <> $oldAmount) {
                $percentage = ($dto->amount * 100) / $oldAmount;
            }

            $data = $dto->toArray();
            $data['percentage'] = $percentage ?? 0;

            DB::transaction(function () use ($rolePrefix, $exchangeRateId, $data) {
                $this->repo($rolePrefix)->update($exchangeRateId, $data);
            });

            return true;
        } catch (\Throwable $e) {
            // Aquí podrías hacer logging del error si lo deseas
            return false;
        }
    }

    /**
     * Elimina un tipo de cambio por ID.
     *
     * @param ExchangeRateDTO $dto Debe contener ID válido
     * @param string $role Rol del propietario: 'commerce' | 'customer'
     * @return bool True si la transacción fue exitosa, false en caso de error.
     *
     * @throws DomainException Si el ID no está definido
     */
    public function delete(ExchangeRateDTO $dto, string $role): bool
    {
        throw_if(!$dto->id, DomainException::class, 'ID requerido para eliminar.');

        try {
            $this->repo($role)->delete($dto->id);
            return true;
        } catch (\Throwable $e) {
            // Puedes agregar aquí logging si lo deseas
            return false;
        }
    }

    /**
     * Devuelve el repositorio correspondiente según el rol.
     *
     * @param string $rolePrefix (prefijo)
     * @return CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface
     *
     * @throws DomainException Si el rol no es válido
     */
    private function repo(string $rolePrefix): CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface
    {
        return match ($rolePrefix) {
            'commerce' => $this->commerce,
            'customer' => $this->customer,
            default => throw new DomainException("Rol inválido: {$rolePrefix}"),
        };
    }
}
