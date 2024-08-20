<?php

namespace App\Traits;

use App\Models\ProviderRKE;
use App\Models\Transactions;

trait SeamlessWallet
{
    private function globalCurl($provider, $bodyString, $method)
    {
        $pvr = ProviderRKE::getById($provider);

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
        $pvr = ProviderRKE::getById($provider, ['brand', 'product']);
        // Datos del cuerpo de la solicitud
        $bodyArray = [
            "Ticks" => "0000000",
            "Product" => $pvr->product,
            "Brand" => $pvr->brand,
            "AdditionalData" => "{Teste Bingo}",
            "Token" => $token // 952549b3-45ae-11ef-aadf-bc2411bfd8ae
        ];

        $bodyString = json_encode($bodyArray);

        return $this->globalCurl($provider, $bodyString, 'GetUserInfo');
    }

    public function getUserBalanceFromProvider($provider, $userToken, $game = '')
    {
        $pvr = ProviderRKE::getById($provider, ['brand', 'product']);

        // Datos del cuerpo de la solicitud
        $bodyArray = [
            "Ticks" => "0000000",
            "Game" => $pvr->product,
            "Brand" => $pvr->brand,
            "AdditionalData" => null,
            "Token" => $userToken[1], // 952549b3-45ae-11ef-aadf-bc2411bfd8ae
            "UserID" => $userToken[0],
            "CurrencyCode" => ""
        ];

        $bodyString = json_encode($bodyArray);

        return $this->globalCurl($provider, $bodyString, 'GetBalance');
    }

    public function transferMoney($provider, $userToken, $betId, $amount, $transactionId, $transactionType, $game = '')
    {
        $pvr = ProviderRKE::getById($provider, ['brand', 'product']);

        // Datos del cuerpo de la solicitud
        $bodyArray = [
            "Ticks" => "0000000",
            "Game" => $pvr->product,
            "Brand" => $pvr->brand,
            "Token" => $userToken[1],
            "UserID" => $userToken[0],
            "CurrencyCode" => '',
            "BetID" => $betId,
            "Amount" => $amount,
            "TransactionID" => $transactionId,
            "TransactionType" => $transactionType,
            "AdditionalData" => "{}"
        ];

        $bodyString = json_encode($bodyArray);

        return $this->globalCurl($provider, $bodyString, 'TransferMoney');
    }

    public function handleUserProviderSession($provider, $userToken)
    {
        $pvrResponse = $this->getUserInfoFromProvider($provider, $userToken);

        if (!$pvrResponse) return false;

        // Crear la variable de sesión 'userPvr'
        $arrUserToken = [encrypt($pvrResponse['ID']), encrypt($userToken)];
        session()->put('userPvr', $arrUserToken);
    }

    public function getTransaction($game, $provider, $user)
    {
        // Obtener la marca de tiempo fuera del bucle
        $timestamp = time();

        do {
            // Concatenar los valores con una cadena aleatoria para asegurar unicidad
            $randomString = bin2hex(random_bytes(5)); // Genera una cadena aleatoria
            $data = 'RKE' . $game . $provider . $user . $timestamp . $randomString . 'RKE';

            // Generar un hash MD5 del valor concatenado
            $uniqueId = md5($data);
        } while (Transactions::existsCode($uniqueId, $provider));

        return $uniqueId;
    }

    public function registerTransaction($game = [], $provider, $userPvr, $type, $amount, $status = 0)
    {
        try {
            Transactions::create([
                'code' => $this->getTransaction($game, $provider, $userPvr),
                'provider' => $provider,
                'game' => $game[0],
                'game_mode' => $game[1],
                'user_pvr' => $userPvr,
                'transaction_type' => $type,
                'amount' => $amount,
                'status' => $status
            ]);
            return true;
        } catch (\Throwable $th) {
            //throw $th;
            return false;
        }
    }
}
