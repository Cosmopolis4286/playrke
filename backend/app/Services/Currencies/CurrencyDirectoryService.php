<?php

namespace App\Services\Currencies;

use App\Models\Currencies\Currency;
use App\Models\Payments\Brasil\PixKey;
use App\Models\Payments\Venezuela\PagoMovil;
use App\Models\Users\Roles\Commerce\Commerce;
use App\Models\Users\Roles\Commerce\CommerceExchangeRate;
use App\Models\Users\Roles\Customer\Customer;
use App\Models\Users\Roles\Seller\Seller;
use App\Repositories\Users\Roles\Contracts\RoleUserRepositoryContract;
use Illuminate\Support\Facades\DB;

class CurrencyDirectoryService
{
    public function __construct(
        protected RoleUserRepositoryContract $roleUserRepository,
    ) {}

    /** Sincroniza los directorios de monedas para un usuario dado.
     *  @param int $userId
     *  @param array $currencyIds
     *  @return void
     */
    public function sync(int $userId, array $currencyIds): void
    {
        // Resolver el ID del propietario del directorio
        $directoryOwnerId = $this->resolveDirectoryOwnerId($userId);

        if (!$directoryOwnerId) {
            return;
        }

        // Obtener los modelos de moneda
        $currencyModels = Currency::getCurrenciesById(
            $currencyIds,
            ['id', 'iso_code']
        );

        // Filtrar las monedas sin directorio
        $withoutDirectories = $currencyModels
            ->filter(
                fn($currency) =>
                $this->hasNoDirectory($directoryOwnerId, $currency->iso_code)
            )
            ->pluck('id')
            ->values()
            ->all();

        if (empty($withoutDirectories)) {
            return;
        }

        DB::transaction(function () use ($directoryOwnerId, $withoutDirectories) {
            // Obtener el ID de la comercializadora
            $commerceId = Commerce::getValueByUserId($directoryOwnerId, 'id');

            if (!$commerceId) {
                return;
            }

            // Obtener los IDs de las últimas tasas de cambio por moneda
            $latestIds = CommerceExchangeRate::selectRaw('MAX(id) as id')
                ->where('commerce', $commerceId)
                ->groupBy('currency')
                ->pluck('id');

            // Actualizar los estados de las tasas de cambio
            CommerceExchangeRate::where('commerce', $commerceId)
                ->whereIn('id', $latestIds)
                ->where('exchange_rate', 0)
                ->whereIn('status', ['ACTIVE', 'DIRECTORY'])
                ->update(['status' => 'INACTIVE']);

            CommerceExchangeRate::where('commerce', $commerceId)
                ->whereIn('id', $latestIds)
                ->where('exchange_rate', '>', 0)
                ->whereIn('currency', $withoutDirectories)
                ->where('status', '!=', 'DIRECTORY')
                ->update(['status' => 'DIRECTORY']);
        });
    }

    /** Resuelve el ID del propietario del directorio según el rol del usuario.
     *  @param int $userId
     *  @return int|null
     */
    private function resolveDirectoryOwnerId(int $userId): ?int
    {
        if (
            $this->roleUserRepository->userHasRolePrefix($userId, 'commerce')
            || $this->roleUserRepository->userHasRolePrefix($userId, 'admin')
        ) {
            return $userId;
        }

        if ($this->roleUserRepository->userHasRolePrefix($userId, 'seller')) {
            return Seller::getValueByUserId($userId, 'user_id');
        }

        if ($this->roleUserRepository->userHasRolePrefix($userId, 'customer')) {
            return Customer::getValueByUserId($userId, 'user_id');
        }

        return null;
    }

    /** Verifica si un usuario no tiene directorio para una moneda específica.
     *  @param int $userId
     *  @param string $iso Código ISO de la moneda
     *  @return bool
     */
    private function hasNoDirectory(int $userId, string $iso): bool
    {
        return match ($iso) {
            'VES' => !PagoMovil::where('user_id', $userId)->exists(),
            'BRL' => !PixKey::where('user_id', $userId)->exists(),
            'COP' => true,
            default => throw new \InvalidArgumentException("Moneda no soportada: {$iso}")
        };
    }
}
