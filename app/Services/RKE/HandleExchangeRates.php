<?php

namespace App\Services\RKE;

use App\Models\Coin;
use App\Models\Commerce;
use App\Models\CommerceExchangeRate;
use App\Models\Customer;
use App\Models\CustomerExchangeRate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class HandleExchangeRates
{
    public function getExchangeRates()
    {
        // Inicializa cURL
        $ch = curl_init();

        try {
            // Establece la URL de la API
            curl_setopt($ch, CURLOPT_URL, "https://smcvenezuela.xyz/dolarsmcbot/binance-api.php");

            // Indica que la respuesta de la solicitud debe ser devuelta como string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Ejecuta la solicitud cURL
            $response = curl_exec($ch);

            // Verifica si hubo un error en la solicitud
            if (curl_errno($ch)) {
                throw new Exception('Error: ' . curl_error($ch));
            }

            // Decodifica la respuesta JSON en un array asociativo
            $data = json_decode($response, true);

            // Verifica si la decodificación fue exitosa
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error al decodificar el JSON: ' . json_last_error_msg());
            }

            return $data;
        } catch (Exception $e) {
            // Manejo del error (puedes registrar el error o realizar otra acción)
            error_log($e->getMessage());
            return null;
        } finally {
            // Cierra el recurso cURL
            curl_close($ch);
        }
    }

    public function updateExchangeRates($userID = '', $checkUpdate = false)
    {
        DB::beginTransaction();

        try {

            $creator = User::getCreatorUser($userID, false, true);

            $data = $this->getExchangeRates();

            switch ($creator['type']) {

                case 'commerce':

                    if ($creator) {
                        $commerces = [$creator['id']];
                    } else {
                        $commerces = Commerce::where('status', 1)->pluck('id');
                    }

                    if ($checkUpdate) {
                        $lastUpdate = CommerceExchangeRate::where('status', 1)
                            ->where('commerce', $creator['id'])
                            ->orderByDesc('id')->value('updated_at');

                        if ($lastUpdate && Carbon::parse($lastUpdate)->addHours(3) > now()) {
                            return;
                        }
                    }

                    foreach ($commerces as $commerceID) {

                        foreach ($data as $coin => $newExchangeRate) {

                            if ($coin === 'Promedio') continue;

                            $coin = Coin::getValueByISOCode($coin, 'id');

                            if (!$coin) continue;

                            $currentRate = CommerceExchangeRate::getCommerceCoinValue($commerceID, $coin);

                            $pct = $currentRate != 0
                                ? round((($newExchangeRate * 100) / $currentRate) - 100, 2)
                                : 0;

                            CommerceExchangeRate::updateOrCreateExchangeRate($commerceID, $coin, $newExchangeRate, $pct);
                        }
                    }

                    break;
                case 'customer':

                    if ($creator) {
                        $customers = [$creator['id']];
                    } else {
                        $customers = Customer::where('status', 1)->pluck('id');
                    }

                    if ($checkUpdate) {
                        $lastUpdate = CustomerExchangeRate::where('status', 1)
                            ->where('customer', $creator['id'])
                            ->orderByDesc('id')->value('updated_at');

                        if ($lastUpdate && Carbon::parse($lastUpdate)->addHours(3) > now()) {
                            return;
                        }
                    }

                    foreach ($customers as $customerID) {

                        foreach ($data as $coin => $newExchangeRate) {

                            if ($coin === 'Promedio') continue;

                            $currentRate = CustomerExchangeRate::getCustomerCoinValue($customerID, $coin);

                            $pct = $currentRate != 0
                                ? round((($newExchangeRate * 100) / $currentRate) - 100, 2)
                                : 0;

                            CustomerExchangeRate::updateOrCreateExchangeRate($customerID, $coin, $newExchangeRate, $pct);
                        }
                    }
                    break;

                default:
                    # code...
                    break;
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al procesar la actualización de las tasas de cambio: " . $e->getMessage());
            return ['Error' => 'Error al procesar la actualización de las tasas de cambio'];
        }
    }
}
