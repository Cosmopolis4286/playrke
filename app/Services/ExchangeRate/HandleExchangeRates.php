<?php

namespace App\Services\ExchangeRate;

use App\Models\{Coin, Commerce, CommerceExchangeRate, Customer, CustomerExchangeRate, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ExchangeRateService
{
    /**
     * Obtener las tasas de cambio desde la API externa.
     *
     * @return array|null
     */
    public function fetchFromApi(): ?array
    {
        $ch = curl_init();

        try {
            curl_setopt($ch, CURLOPT_URL, "https://smcvenezuela.xyz/dolarsmcbot/binance-api.php");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new Exception('Error en cURL: ' . curl_error($ch));
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
            }

            return $data;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        } finally {
            curl_close($ch);
        }
    }

    /**
     * Actualiza las tasas de cambio para un usuario específico o para todos si no se pasa userID.
     *
     * @param int|null $userID
     * @param bool $checkUpdate
     * @return bool|array
     */
    public function updateRates(?int $userID = null, bool $checkUpdate = false): bool|array
    {
        $data = $this->fetchFromApi();
        if (!$data) return ['Error' => 'No se pudieron obtener las tasas de cambio.'];

        DB::beginTransaction();

        try {
            $creator = $userID ? User::getCreatorUser($userID, false, true) : null;

            if (!$creator) {
                $this->updateForAll('commerce', $data, $checkUpdate);
                $this->updateForAll('customer', $data, $checkUpdate);
            } else {
                $this->updateForType($creator['type'], [$creator['id']], $data, $checkUpdate);
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error al actualizar tasas: " . $e->getMessage());
            return ['Error' => 'Error al procesar la actualización de las tasas de cambio'];
        }
    }

    /**
     * Actualiza las tasas de cambio para todos los registros de un tipo específico.
     *
     * @param string $type 'commerce' o 'customer'
     * @param array $data
     * @param bool $checkUpdate
     * @return void
     */
    private function updateForAll(string $type, array $data, bool $checkUpdate): void
    {
        $model = $type === 'commerce' ? Commerce::class : Customer::class;
        $ids = $model::where('status', 1)->pluck('id')->toArray();
        $this->updateForType($type, $ids, $data, $checkUpdate);
    }

    /**
     * Actualiza las tasas de cambio para IDs específicos de un tipo.
     *
     * @param string $type
     * @param array $ids
     * @param array $data
     * @param bool $checkUpdate
     * @return void
     */
    private function updateForType(string $type, array $ids, array $data, bool $checkUpdate): void
    {
        $exchangeModel = $type === 'commerce' ? CommerceExchangeRate::class : CustomerExchangeRate::class;

        foreach ($ids as $id) {

            if ($checkUpdate) {
                $lastUpdate = $exchangeModel::where('status', 'ACTIVE')
                    ->where($type, $id)
                    ->orderByDesc('id')
                    ->value('updated_at');

                if ($lastUpdate && Carbon::parse($lastUpdate)->addHours(3) > now()) {
                    continue;
                }
            }

            foreach ($data as $coinIso => $newRate) {
                if ($coinIso === 'Promedio' || $newRate == 0) continue;

                $coinId = Coin::getValueByISOCode($coinIso, 'id');
                if (!$coinId) continue;

                $currentRate = $exchangeModel::getValue($id, $coinId);
                $pct = $currentRate != 0 ? round((($newRate * 100) / $currentRate) - 100, 2) : 0;

                $exchangeModel::updateOrCreateExchangeRate($id, $coinId, $newRate, $pct);
            }
        }
    }
}
