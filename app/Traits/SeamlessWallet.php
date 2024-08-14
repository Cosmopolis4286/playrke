<?php

namespace App\Traits;

use App\Models\ProviderRKE;
use App\Models\UserProvider;

trait SeamlessWallet
{
    private function globalCurl($provider, $bodyString, $method)
    {
        $pvr = ProviderRKE::getById(decrypt($provider));

        // Clave de seguridad (deberías obtenerla de un lugar seguro, como un archivo de configuración)
        $securityKey = env($pvr->key);

        // Concatenar el cuerpo de la solicitud con la clave de seguridad
        $combinedString = $bodyString . $securityKey;

        // Generar el hash SHA-256 y convertirlo a hexadecimal
        $hashHex = hash('sha256', $combinedString);

        // Inicializar CURL
        $curl = curl_init();

        // Configurar las opciones de CURL
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://{$pvr->link_route}/{$method}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $bodyString, // Pasar el cuerpo como JSON
            CURLOPT_HTTPHEADER => array(
                "X-Signature: $hashHex",  // Incluir la firma en el encabezado
                'Content-Type: application/json'
            ),
        ));

        // Ejecutar la solicitud
        $response = curl_exec($curl);

        // Cerrar la conexión CURL
        curl_close($curl);

        // Mostrar la respuesta
        return $response;
    }

    public function getUserInfoFromProvider($provider, $token)
    {
        $pvr = ProviderRKE::getById(decrypt($provider), ['brand', 'product']);
        // Datos del cuerpo de la solicitud
        $bodyArray = [
            "Ticks" => "0000000",
            "Product" => $pvr->product,
            "Brand" => $pvr->brand,
            "AdditionalData" => "{}",
            "Token" => $token // 952549b3-45ae-11ef-aadf-bc2411bfd8ae
        ];

        $bodyString = json_encode($bodyArray);

        $res = $this->globalCurl($provider, $bodyString, 'GetUserInfo');
    }

    public function getUserBalanceFromProvider($provider, $token, $userId)
    {
        $user = UserProvider::getByUserID($userId, ['user_id', 'coin']);

        // Datos del cuerpo de la solicitud
        $bodyArray = [
            "Ticks" => "0000000",
            "Game" => "RoyalEis",
            "Brand" => "APUESTASROYEL",
            "AdditionalData" => null,
            "Token" => $token, // 952549b3-45ae-11ef-aadf-bc2411bfd8ae
            "UserID" => $user->user_id,
            "CurrencyCode" => $user->coin
        ];

        $bodyString = json_encode($bodyArray);

        $res = $this->globalCurl($provider, $bodyString, 'GetBalance');
    }

    public function transferMoney($provider, $token, $userId, $betId, $amount, $transactionId, $transactionType)
    {
        $user = UserProvider::getByUserID($userId, ['user_id', 'coin']);

        // Datos del cuerpo de la solicitud
        $bodyArray = [
            "Ticks" => "0000000",
            "Game" => "RoyalEis",
            "Brand" => "APUESTASROYEL",
            "Token" => $token,
            "UserID" => $user->user_id,
            "CurrencyCode" => $user->coin,
            "BetID" => $betId,
            "Amount" => $amount,
            "TransactionID" => $transactionId,
            "TransactionType" => $transactionType,
            "AdditionalData" => "{}"
        ];

        $bodyString = json_encode($bodyArray);

        $res = $this->globalCurl($provider, $bodyString, 'TransferMoney');
    }
}
