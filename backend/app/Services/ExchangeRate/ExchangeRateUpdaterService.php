<?php

namespace App\Services\ExchangeRate;

use App\Contracts\ExchangeRateRepositoryInterface;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Models\Roles\Customer\CustomerExchangeRate;
use App\Services\Roles\RoleUserService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para actualizar tasas de cambio desde una API externa
 * y sincronizarlas con los registros de comercio y cliente.
 */
final class ExchangeRateUpdaterService
{
    public function __construct(
        protected ExchangeRateRepositoryInterface $repository,
        protected RoleUserService $roleUserService
    ) {}

    /**
     * Actualiza las tasas de cambio para un usuario específico,
     * distribuyendo la actualización según su rol activo.
     *
     * @param int $userId ID del usuario activo
     * @return bool Indica si la operación fue exitosa
     */
    public function updateRates(int $userId): bool
    {
        $rolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);
        $data = $this->fetchFromApi();

        if (empty($data)) {
            Log::warning('No se obtuvieron datos para actualizar tasas.');
            return false;
        }

        try {
            $this->updateForType($rolePrefix, [$userId], $data, true);
            return true;
        } catch (Exception $e) {
            Log::error('Error actualizando tasas: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene las tasas de cambio desde la API externa.
     *
     * @return array|null Datos decodificados o null en caso de error
     */
    public function fetchFromApi(): ?array
    {
        $url = "https://smcvenezuela.xyz/dolarsmcbot/binance-api.php";

        try {
            $response = file_get_contents($url);
            if ($response === false) {
                throw new Exception('No se pudo obtener respuesta desde la API.');
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
            }

            return $data;
        } catch (Exception $e) {
            Log::error('fetchFromApi error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualiza las tasas para todos los registros activos de un tipo (commerce o customer).
     *
     * @param string $type Tipo de entidad ('commerce' | 'customer')
     * @param array $data Tasas desde la API
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
     * @param string $type Tipo de entidad ('commerce' | 'customer')
     * @param array $ids IDs específicos a actualizar
     * @param array $data Tasas desde la API
     * @return void
     */
    public function updateForType(string $type, array $ids, array $data): void
    {
        $exchangeModel = $this->getExchangeModelClass($type);

        foreach ($ids as $id) {
            if ($this->shouldSkipUpdate($exchangeModel, $type, $id)) {
                continue;
            }

            foreach ($data as $currencyIso => $newRate) {
                if ($currencyIso === 'Promedio' || $newRate == 0) {
                    continue;
                }

                $currencyId = Currency::getValueByISOCode($currencyIso, 'id');
                if (!$currencyId) continue;

                $currentRate = $exchangeModel::getValue($id, $currencyId);
                $percentageChange = $currentRate != 0
                    ? round((($newRate * 100) / $currentRate) - 100, 2)
                    : 0;

                $exchangeModel::updateOrCreateExchangeRate($id, $currencyId, $newRate, $percentageChange);
            }
        }
    }

    /**
     * Determina si se debe omitir la actualización basándose en la última actualización.
     *
     * @param string $exchangeModel Modelo de tasas
     * @param string $type Tipo de entidad
     * @param int|string $id ID a verificar
     * @return bool True si debe omitirse la actualización
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
        return $type === 'commerce' ? \App\Models\Commerce::class : \App\Models\Customer::class;
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
