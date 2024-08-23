<?php

namespace App\Traits;

use App\Models\Customer;
use App\Models\Transactions;
use Illuminate\Support\Facades\Log;

trait SeamlessWallet
{
    private function globalCurl($customer, $bodyString, $method)
    {
        $customer = Customer::getById($customer);

        // Clave de seguridad (deberías obtenerla de un lugar seguro, como un archivo de configuración)
        $securityKey = env($customer->key);

        // Concatenar el cuerpo de la solicitud con la clave de seguridad
        $combinedString = $bodyString . $securityKey;

        // Generar el hash SHA-256 y convertirlo a hexadecimal
        $hashHex = hash('sha256', $combinedString);

        // Inicializar CURL
        $curl = curl_init();

        // Configurar las opciones de CURL
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://{$customer->link_route}/{$method}",
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

    public function getUserInfoFromCustomer($customerId, $token)
    {
        $customer = Customer::getById($customerId, ['brand', 'product']);
        // Datos del cuerpo de la solicitud
        $bodyArray = [
            "Ticks" => "0000000",
            "Product" => $customer->product,
            "Brand" => $customer->brand,
            "AdditionalData" => "{Teste Bingo}",
            "Token" => $token // 952549b3-45ae-11ef-aadf-bc2411bfd8ae
        ];

        $bodyString = json_encode($bodyArray);

        return $this->globalCurl($customer, $bodyString, 'GetUserInfo');
    }

    public function getUserBalanceFromCustomer($customer, $userToken, $game = '')
    {
        $customer = Customer::getById($customer, ['brand', 'product']);

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

        $bodyString = json_encode($bodyArray);

        return $this->globalCurl($customer, $bodyString, 'GetBalance');
    }

    public function transferMoney($customer, $userToken, $betId, $coin, $amount, $transactionId, $transactionType, $game = '')
    {
        // Obtener datos del proveedor
        $customer = Customer::getById($customer, ['brand', 'product']);

        // Validar que se obtuvieron datos del proveedor
        if (!$customer) {
            Log::error("Cliente no encontrado: ID {$customer}");
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

        $bodyString = json_encode($bodyArray);

        // Llamada al método globalCurl
        $response = $this->globalCurl($customer, $bodyString, 'TransferMoney');

        // Manejo de la respuesta
        if (isset($response['Error']) && $response['Error']) {
            Log::error("Error en la transferencia de dinero: " . ($response['Message'] ?? 'Error desconocido'));
        }

        return $response;
    }

    public function handleCustomerUserSession($customer, $userToken)
    {
        $customerResponse = $this->getUserInfoFromCustomer($customer, $userToken);

        if (!$customerResponse) return false;

        // Crear la variable de sesión 'clientUser'
        session()->put('clientUser', encrypt([$customerResponse['ID'], $userToken]));
        session()->put('coinClientUser', encrypt($customerResponse['DefaultCurrencyCode']));
    }

    public function getTransaction($game, $customer, $user)
    {
        // Obtener la marca de tiempo fuera del bucle
        $timestamp = time();

        do {
            // Concatenar los valores con una cadena aleatoria para asegurar unicidad
            $randomString = bin2hex(random_bytes(5)); // Genera una cadena aleatoria
            $data = 'RKE' . $game . $customer . $user . $timestamp . $randomString . 'RKE';

            // Generar un hash MD5 del valor concatenado
            $uniqueId = md5($data);
        } while (Transactions::existsCode($uniqueId, $customer));

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
