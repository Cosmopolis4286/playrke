<?php

namespace App\Services\Currencies;

use App\Models\Directories\Fiat\Brazil\PixKey;
use App\Models\Directories\Fiat\Venezuela\PagoMovil;
use App\Models\Roles\Customer;
use App\Models\Roles\Seller;
use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\Roles\RoleUserRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CurrencyDirectoryService
{
    public function __construct(
        protected RoleUserRepositoryInterface $roleUserRepository,
        protected CurrencyRepositoryInterface $currencyRepository,
    ) {}

    /**
     * Sincroniza los directorios de monedas para un usuario dado.
     *
     * @param int   $userId
     * @param array $currencyIds
     * @return void
     */
    public function sync(int $userId, array $currencyIds): void
    {
        $directoryOwnerId = $this->resolveDirectoryOwnerId($userId);

        if (!$directoryOwnerId || empty($currencyIds)) {
            return;
        }

        $currencies = $this->currencyRepository->getByIds(
            $currencyIds,
            ['id', 'iso_code']
        );

        $currencyIdsWithoutDirectory = $currencies
            ->filter(
                fn($currency) =>
                $this->hasNoDirectory($directoryOwnerId, $currency->iso_code)
            )
            ->pluck('id')
            ->all();

        if (empty($currencyIdsWithoutDirectory)) {
            return;
        }

        DB::transaction(function () use ($directoryOwnerId, $currencyIdsWithoutDirectory) {
            $commerceId = Commerce::getValueByUserId($directoryOwnerId, 'id');

            if (!$commerceId) {
                return;
            }

            // Obtener los IDs de las últimas tasas de cambio por moneda
            $latestExchangeRateIds = CommerceExchangeRate::query()
                ->where('commerce', $commerceId)
                ->selectRaw('MAX(id) as id')
                ->groupBy('currency')
                ->pluck('id');
            // Actualizar los estados de las tasas de cambio
            CommerceExchangeRate::where('commerce', $commerceId)
                ->whereIn('id', $latestExchangeRateIds)
                ->where('exchange_rate', 0)
                ->whereIn('status', ['ACTIVE', 'DIRECTORY'])
                ->update(['status' => 'INACTIVE']);

            CommerceExchangeRate::where('commerce', $commerceId)
                ->whereIn('id', $latestExchangeRateIds)
                ->where('exchange_rate', '>', 0)
                ->whereIn('currency', $currencyIdsWithoutDirectory)
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
