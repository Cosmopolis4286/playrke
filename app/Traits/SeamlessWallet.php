<?php

namespace App\Traits;

use App\Models\Customer;
use App\Models\Transactions;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

trait SeamlessWallet
{
    private function generateSignature($dataJson, $securityKey)
    {
        // Concatenar el cuerpo de la solicitud con la clave de seguridad
        $combinedString = $dataJson . $securityKey;
        //Generar el hash SHA-256 y convertirlo a hexadecimal
        return bin2hex(hash('sha256', $combinedString, true));
    }

    private function getCustomerKey($customerID)
    {
        $customer = Customer::getById($customerID, ['customer_key', 'security_key']);

        // Desencriptar clave de seguridad 
        $encryptedKey = decrypt($customer->security_key, env('CTMER_ENCRYPTION_KEY'));

        // Desencriptar la clave de seguridad del cliente usando la clave específica
        return decrypt($customer->customer_key, $encryptedKey);
    }

    private function globalCurl($customerID, $dataJson, $action)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://apipolla.apuestasroyal.com/GetUserInfo',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "Ticks": "0000000",
            "Product": "RoyalEis",
            "Brand": "APUESTASROYAL",
            "AdditionalData": "{}",
            "Token": "952549b3-45ae-11ef-aadf-bc2411bfd8ae"
        }',
            CURLOPT_HTTPHEADER => array(
                'X-Signature: 32c07f1676ceae90104f2e293e61e4507ee53b5de435d4f692b23e423f5e8598',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
        die;

        $customerKey = self::getCustomerKey($customerID);
        $linkRoute = Customer::getValueById($customerID, 'link_route');

        // Generar el hash SHA-256 y convertirlo a hexadecimal
        $hashHex = self::generateSignature($dataJson, $customerKey);

        // Inicializar CURL
        $curl = curl_init();

        // Configurar las opciones de CURL
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://{$linkRoute}/{$action}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $dataJson, // Pasar el cuerpo como JSON
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

    private function globalGuzzle($customerID, $dataJson, $action)
    {
        $customerKey = self::getCustomerKey($customerID);
        $linkRoute = Customer::getValueById($customerID, 'link_route');

        // Generar el hash SHA-256 y convertirlo a hexadecimal
        $hashHex = self::generateSignature($dataJson, $customerKey);

        $client = new Client();

        $headers = [
            'X-Signature' => '32c07f1676ceae90104f2e293e61e4507ee53b5de435d4f692b23e423f5e8598',
            'Content-Type' => 'application/json'
        ];

        $request = new Request('POST', "http://apipolla.apuestasroyal.com/{$action}", $headers, $dataJson);
        $res = $client->sendAsync($request)->wait();
        return $res->getBody();
    }

    public function getUserInfoFromCustomer($customerID, $token)
    {
        $customer = Customer::getById($customerID, ['brand', 'product']);
        // Datos del cuerpo de la solicitud
        $bodyArray = [
            "Ticks" => "0000000",
            "Product" => 'RoyalEis',
            "Brand" => 'APUESTASROYAL',
            "AdditionalData" => "{}",
            "Token" => "952549b3-45ae-11ef-aadf-bc2411bfd8ae" //$token // 952549b3-45ae-11ef-aadf-bc2411bfd8ae
        ];

        $dataJson = json_encode($bodyArray);

        return $this->globalCurl($customerID, $dataJson, 'GetUserInfo');
        //return self::globalGuzzle($customerID, $dataJson, 'GetUserInfo');
    }

    public function getUserBalanceFromCustomer($customerID, $userToken, $game = '')
    {
        $customer = Customer::getById($customerID, ['brand', 'product']);

        // Datos del cuerpo de la solicitud
        $bodyArray = [
            "Ticks" => "0000000",
            "Game" => $customer->product,
            "Brand" => $customer->brand,
            "AdditionalData" => null,
            "Token" => $userToken[1], // 952549b3-45ae-11ef-aadf-bc2411bfd8ae
            "UserID" => $userToken[0],
            "CurrencyCode" => ""
        ];

        $dataJson = json_encode($bodyArray);

        return $this->globalCurl($customerID, $dataJson, 'GetBalance');
    }

    public function transferMoney($customerID, $userToken, $betId, $coin, $amount, $transactionId, $transactionType, $game = '')
    {
        // Obtener datos del proveedor
        $customer = Customer::getById($customerID, ['brand', 'product']);

        // Validar que se obtuvieron datos del proveedor
        if (!$customer) {
            Log::error("Cliente no encontrado: ID {$customerID}");
            return ['Error' => true, 'Message' => 'Cliente no encontrado'];
        }

        // Datos del cuerpo de la solicitud
        $bodyArray = [
            "Ticks" => "0000000", // Asegúrate de que este valor es correcto
            "Game" => $customer->product,
            "Brand" => $customer->brand,
            "Token" => $userToken[1] ?? '',
            "UserID" => $userToken[0] ?? '',
            "CurrencyCode" => $coin,
            "BetID" => $betId,
            "Amount" => $amount,
            "TransactionID" => $transactionId,
            "TransactionType" => $transactionType,
            "AdditionalData" => "{}"
        ];

        $dataJson = json_encode($bodyArray);

        // Llamada al método globalCurl
        $response = $this->globalCurl($customerID, $dataJson, 'TransferMoney');

        // Manejo de la respuesta
        if (isset($response['Error']) && $response['Error']) {
            Log::error("Error en la transferencia de dinero: " . ($response['Message'] ?? 'Error desconocido'));
        }

        return $response;
    }

    public function handleCustomerUserSession($customerID, $userToken)
    {
        $customerResponse = $this->getUserInfoFromCustomer($customerID, $userToken);

        dd($customerResponse);

        if (!$customerResponse) return false;

        // Crear la variable de sesión 'clientUser'
        session()->put('clientUser', encrypt([$customerResponse['ID'], $userToken]));
        session()->put('coinClientUser', encrypt($customerResponse['DefaultCurrencyCode']));
    }

    public function getTransaction($game, $customerID, $user)
    {
        // Obtener la marca de tiempo fuera del bucle
        $timestamp = time();

        do {
            // Concatenar los valores con una cadena aleatoria para asegurar unicidad
            $randomString = bin2hex(random_bytes(5)); // Genera una cadena aleatoria
            $data = 'RKE' . $game . $customerID . $user . $timestamp . $randomString . 'RKE';

            // Generar un hash MD5 del valor concatenado
            $uniqueId = md5($data);
        } while (Transactions::existsCode($uniqueId, $customerID));

        return $uniqueId;
    }

    public function registerTransaction($game = [], $customer, $clientUser, $type, $amount, $status = 0)
    {
        try {
            $transaction = Transactions::create([
                'code' => $this->getTransaction($game, $customer, $clientUser),
                'customer' => $customer,
                'game' => $game[0],
                'game_mode' => $game[1],
                'user_customer' => $clientUser,
                'transaction_type' => $type,
                'amount' => $amount,
                'status' => $status
            ]);
            return [$transaction->id, $transaction->code];
        } catch (\Throwable $th) {
            //throw $th;
            return false;
        }
    }
}
