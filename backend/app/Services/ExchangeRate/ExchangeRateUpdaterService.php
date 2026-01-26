<?php

namespace App\Services\ExchangeRate;

use App\Contracts\ExchangeRateRepositoryInterface;
use App\Models\Currency;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Models\Roles\Customer\CustomerExchangeRate;
use App\Services\Roles\RoleUserService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para actualizar tasas de cambio desde una API externa
 * y sincronizarlas con registros de comercio y cliente.
 */
final class ExchangeRateUpdaterService
{
    public function __construct(
        protected readonly ExchangeRateRepositoryInterface $repository,
        protected readonly RoleUserService $roleUserService,
        protected readonly ExchangeRateApiService $api
    ) {}

    /**
     * Actualiza las tasas de cambio para el rol activo de un usuario dado.
     *
     * @param int $userId ID del usuario activo
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function updateRates(int $userId): bool
    {
        $rolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);
        $data = $this->api->fetch();

        if (empty($data)) {
            Log::warning('No se obtuvieron datos para actualizar tasas.');
            return false;
        }

        try {
            $this->updateForType($rolePrefix, [$userId], $data, true);
            return true;
        } catch (\Throwable $e) {
            Log::error('Error actualizando tasas: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza las tasas para todos los registros activos de un tipo (commerce o customer).
     *
     * @param string $type Tipo de entidad ('commerce'|'customer')
     * @param array $data Tasas obtenidas desde la API
     * @return void
     */
    public function updateForAll(string $type, array $data): void
    {
        $model = $this->getModelClass($type);
        $ids = $model::where('status', 1)->pluck('id')->toArray();
        $this->updateForType($type, $ids, $data);
    }

    /**
     * Actualiza las tasas para un conjunto específico de IDs de un tipo.
     *
     * @param string $type Tipo de entidad ('commerce'|'customer')
     * @param array $ids IDs a actualizar
     * @param array $data Tasas obtenidas desde la API
     * @param bool $skipIfRecentlyUpdated Indica si se debe omitir actualización si fue reciente
     * @return void
     */
    public function updateForType(string $type, array $ids, array $data, bool $skipIfRecentlyUpdated = false): void
    {
        $exchangeModel = $this->getExchangeModelClass($type);

        foreach ($ids as $id) {
            if ($skipIfRecentlyUpdated && $this->shouldSkipUpdate($exchangeModel, $type, $id)) {
                continue;
            }

            foreach ($data as $currencyIso => $newRate) {
                if ($currencyIso === 'Promedio' || $newRate == 0) {
                    continue;
                }

                $currencyId = Currency::getValueByISOCode($currencyIso, 'id');
                if (!$currencyId) {
                    continue;
                }

                $currentRate = $exchangeModel::getValue($id, $currencyId);
                $percentageChange = $currentRate != 0
                    ? round((($newRate * 100) / $currentRate) - 100, 2)
                    : 0;

                $exchangeModel::updateOrCreateExchangeRate($id, $currencyId, $newRate, $percentageChange);
            }
        }
    }

    /**
     * Determina si se debe omitir la actualización basado en la última actualización.
     *
     * @param string $exchangeModel Modelo de tasas
     * @param string $type Tipo de entidad
     * @param int|string $id ID a verificar
     * @return bool True si la actualización fue hace menos de 3 horas
     */
    protected function shouldSkipUpdate(string $exchangeModel, string $type, int|string $id): bool
    {
        $lastUpdate = $exchangeModel::where('status', 'ACTIVE')
            ->where($type, $id)
            ->orderByDesc('id')
            ->value('updated_at');

        return $lastUpdate && Carbon::parse($lastUpdate)->addHours(3)->isFuture();
    }

    /**
     * Retorna la clase del modelo según el tipo.
     *
     * @param string $type Tipo ('commerce'|'customer')
     * @return string Nombre completo del modelo
     */
    protected function getModelClass(string $type): string
    {
        return $type === 'commerce' ? \App\Models\Roles\Commerce::class : \App\Models\Roles\Customer::class;
    }

    /**
     * Retorna la clase del modelo de tasas según el tipo.
     *
     * @param string $type Tipo ('commerce'|'customer')
     * @return string Nombre completo del modelo de tasas
     */
    protected function getExchangeModelClass(string $type): string
    {
        return $type === 'commerce'
            ? CommerceExchangeRate::class
            : CustomerExchangeRate::class;
    }
}
