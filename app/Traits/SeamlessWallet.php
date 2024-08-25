<?php

namespace App\Traits;

use App\Models\Customer;
use App\Models\Transactions;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

trait SeamlessWallet
{
    private function getCustomerKey($customerID)
    {
        $customer = Customer::getById($customerID, ['customer_key', 'security_key']);

        // Desencriptar clave de seguridad 
        $encryptedKey = decrypt($customer->security_key, env('CTMER_ENCRYPTION_KEY'));

        // Desencriptar la clave de seguridad del cliente usando la clave específica
        $customerKey = decrypt($customer->customer_key, $encryptedKey);

        return $customerKey;
    }

    private function generateSignature($dataJson, $securityKey)
    {
        // Concatenar el cuerpo de la solicitud con la clave de seguridad
        // Generar el hash SHA-256 y convertirlo a hexadecimal
        return hash('sha256', $dataJson . $securityKey);
    }

    private function globalCurl($customerID, $dataJson, $action)
    {
        // Obtener la clave de seguridad y la ruta del enlace
        $customerKey = $this->getCustomerKey($customerID);
        $linkRoute = Customer::getValueById($customerID, 'link_route');

        // Generar la firma
        $hashHex = $this->generateSignature($dataJson, $customerKey);

        // Inicializar y configurar CURL en una sola llamada
        $curl = curl_init("http://{$linkRoute}/{$action}");
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $dataJson,
            CURLOPT_HTTPHEADER => [
                "X-Signature: $hashHex",
                'Content-Type: application/json',
            ],
        ]);

        // Ejecutar la solicitud y cerrar CURL
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    private function globalGuzzle($customerID, $dataJson, $action)
    {
        // Obtener la clave de seguridad del cliente
        $customerKey = self::getCustomerKey($customerID);

        // Generar la firma
        $signature = $this->generateSignature($dataJson, $customerKey);

        // Obtener la URL base del cliente
        $linkRoute = Customer::getValueById($customerID, 'link_route');

        // Crear una instancia de Guzzle
        $client = new Client();

        // Configurar los encabezados de la solicitud
        $headers = [
            'X-Signature' => $signature,
            'Content-Type' => 'application/json'
        ];

        // Realizar la solicitud HTTP POST con tiempos de espera extendidos
        try {
            $response = $client->post("http://{$linkRoute}/{$action}", [
                'headers' => $headers,
                'body' => $dataJson,
                'timeout' => 30,           // Tiempo máximo de espera de la respuesta en segundos
                'connect_timeout' => 20,   // Tiempo máximo de espera para la conexión en segundos
            ]);

            // Retornar la respuesta del servidor
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            // Manejar errores
            Log::error('Error en la solicitud HTTP: ' . $e->getMessage());
            return null;
        }
    }

    public function getUserInfoFromCustomer($customerID, $token)
    {
        $customer = Customer::getById($customerID, ['brand', 'product']);

        // Datos del cuerpo de la solicitud
        $dataJson = json_encode([
            "Ticks" => "0000000",
            "Product" => 'RoyalEis',
            "Brand" => 'APUESTASROYAL',
            "AdditionalData" => "{}",
            "Token" => $token
        ]);

        //return $this->globalCurl($customerID, $dataJson, 'GetUserInfo');
        return $this->globalGuzzle($customerID, $dataJson, 'GetUserInfo');
    }

    public function getUserBalanceFromCustomer($customerID, $userToken, $game = '')
    {
        $customer = Customer::getById($customerID, ['brand', 'product']);

        // Datos del cuerpo de la solicitud
        $dataJson = json_encode([
            "Ticks" => "0000000",
            "Game" => $customer->product,
            "Brand" => $customer->brand,
            "AdditionalData" => null,
            "Token" => $userToken[1], // 952549b3-45ae-11ef-aadf-bc2411bfd8ae
            "UserID" => $userToken[0],
            "CurrencyCode" => ""
        ]);

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
        $dataJson = json_encode([
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
        ]);

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
        $customerResponse = json_decode($this->getUserInfoFromCustomer($customerID, $userToken));

        if (!$customerResponse) return false;

        // Crear la variable de sesión 'CustomerUser'
        session()->put('CUSTU', encrypt([$customerResponse->Data->ID, $userToken]));
        session()->put(
            'CCCUSTU',
            encrypt([
                $customerResponse->Data->Country,
                $customerResponse->Data->DefaultCurrencyCode
            ])
        );

        return $customerResponse->Data;
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
