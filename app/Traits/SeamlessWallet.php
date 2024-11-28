<?php

namespace App\Traits;

use App\Models\Customer;
use App\Models\CustomerGames;
use App\Models\CustomerUser;
use App\Models\Games;
use App\Models\Transactions;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait SeamlessWallet
{
    use CustomerTrait;

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

        return json_decode($response);
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
            $response = $client->post("{$linkRoute}/{$action}", [
                'headers' => $headers,
                'body' => $dataJson,
                'timeout' => 30,           // Tiempo máximo de espera de la respuesta en segundos
                'connect_timeout' => 20,   // Tiempo máximo de espera para la conexión en segundos
            ]);

            // Retornar la respuesta del servidor
            return json_decode($response->getBody()->getContents());
        } catch (\Exception $e) {
            // Manejar errores
            Log::error('Error en la solicitud HTTP: ' . $e->getMessage());
            return null;
        }
    }

    public function getUserInfoFromCustomer($customerID, $userToken)
    {
        // Datos del cuerpo de la solicitud
        $dataJson = json_encode(["Token" => $userToken]);

        return $this->globalGuzzle($customerID, $dataJson, 'GetUserInfo');
    }

    public function getUserBalanceFromCustomer($customerID, $userToken)
    {
        $user = CustomerUser::getvalueByToken($customerID, $userToken, ['user_id', 'coin']);

        // Datos del cuerpo de la solicitud
        $dataJson = json_encode([
            "Token" => $userToken,
            "UserID" => $user->user_id,
            "CurrencyCode" => $userCoin ?? ''
        ]);

        return $this->globalGuzzle($customerID, $dataJson, 'GetBalance');
    }

    public function transferMoney($customerID, $game, $userToken, $betId, $coin, $amount, $transactionId, $transactionType, $additionalData = '')
    {
        // Obtener datos del proveedor
        $customer = Customer::getById($customerID, ['brand', 'product']);

        // Validar que se obtuvieron datos del proveedor
        if (!$customer) {
            Log::error("Cliente no encontrado: ID {$customerID}");
            return ['Error' => 'Cliente no encontrado'];
        }

        // Obtener Identificador del Juego con respecto al cliente
        $gameID = CustomerGames::getCustomerGameID($customerID, $game);

        if (!$gameID) {
            Log::error("Error al procesar la transacion: '{$transactionId}': No existe el ID de Juego del cliente [{$customerID}] para el juego [games.id={$game}]");
            return ['Error' => "No existe el ID de Juego del cliente [{$customerID}]"];
        }

        if (!is_array($userToken)) {
            $player = CustomerUser::getById($userToken, ['user_id', 'token']);
            $userToken = [$player->user_id, $player->token];
        }

        // Datos del cuerpo de la solicitud
        $dataJson = json_encode([
            "Ticks" => "0000000",
            "Game" => $gameID,
            "Brand" => $customer->brand,
            "Token" => $userToken[1] ?? '',
            "UserID" => $userToken[0] ?? '',
            "CurrencyCode" => $coin,
            "BetID" => $betId,
            "Amount" => $amount,
            "TransactionID" => $transactionId,
            "TransactionType" => $transactionType,
            "AdditionalData" => json_encode($additionalData) ?? "{}"
        ]);

        // Llamada al método globalGuzzle
        $response = $this->globalGuzzle($customerID, $dataJson, 'TransferMoney');

        // Manejo de la respuesta
        if (isset($response->Error) && $response->Error) {
            Log::error("Error en la transferencia de dinero: " . ($response->Error ?? 'Error desconocido'));
            return $response->Error;
        }

        return $response;
    }

    public function getTransaction($game, $customerID, $user)
    {
        // Obtener la marca de tiempo fuera del bucle
        $timestamp = time();

        do {
            // Concatenar los valores con una cadena aleatoria para asegurar unicidad
            $randomString = bin2hex(random_bytes(5)); // Genera una cadena aleatoria
            $data = $game . $customerID . $user . $timestamp . $randomString;

            // Generar un hash MD5 del valor concatenado
            $uniqueId = md5($data);
        } while (Transactions::existsCode($uniqueId, $customerID));

        return $uniqueId;
    }

    // Funcion para manejar una transaccion tipo "BET"
    private function handleTransactionByBET(array $game, $customer, $roomID, $playerUser, $coin, $totalSaleAmount)
    {
        try {
            return DB::transaction(function () use ($game, $customer, $roomID, $playerUser, $coin, $totalSaleAmount) {
                // Registrar la transacción
                $transaction = $this->registerTransaction(
                    $game,
                    $customer,
                    $playerUser,
                    $roomID,
                    'BET',
                    $coin,
                    $totalSaleAmount
                );

                if (!$transaction) {
                    throw new \Exception('Error al registrar la transacción');
                }

                // Procesar la transferencia de dinero
                $transactionResponse = $this->transferMoney(
                    $transaction->customer,
                    $transaction->game,
                    $transaction->user_id,
                    $transaction->bet_id,
                    $transaction->coin,
                    $transaction->amount,
                    $transaction->code,
                    $transaction->transaction_type
                );

                if (isset($transactionResponse->Error) && $transactionResponse->Error) {
                    $errorMessage = "Error al procesar la transacción '{$transaction->id}': {$transactionResponse->Error}";
                    Log::error($errorMessage);
                    throw new \Exception($errorMessage);
                }

                // Actualizar la transacción como exitosa
                $transaction->update([
                    'status' => 1,
                    'processing_data' => now(),
                    'updated_at' => now(),
                ]);

                return ['Success' => true];
            });
        } catch (\Exception $e) {
            Log::error("Error en handleTransactionByBET: " . $e->getMessage());
            return ['Error' => $e->getMessage()];
        }
    }

    // Función para registrar las transacciones del cliente 
    public function registerTransaction(array $game, $customer, $clientUser, $betId, $type, $coin, $amount, $status = 0)
    {
        try {
            // Validar datos requeridos
            if (empty($game['name']) || empty($game['game_mode'])) {
                throw new \InvalidArgumentException('El juego debe tener un nombre y un modo válido.');
            }

            // Obtener valores necesarios
            $gameID = Games::getValueByName($game['name']);
            $code = $this->getTransaction($game['name'], $customer, $clientUser);

            // Crear la transacción
            return Transactions::create([
                'customer' => $customer,
                'user_id' => $clientUser,
                'game' => $gameID,
                'game_mode' => $game['game_mode'],
                'bet_id' => $betId,
                'code' => $code, // transaction_id
                'transaction_type' => $type,
                'coin' => $coin,
                'amount' => $amount,
                'status' => $status,
            ]);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Datos inválidos para registrar la transacción: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('Error al registrar la transacción: ' . $e->getMessage());
            return false;
        }
    }


    public function validateTransaction($customer)
    {
        // Obtener transacciones pendientes
        $transactions = Transactions::where('customer', $customer)->where('status', 0)->get();

        // Verificar si hay transacciones pendientes
        if ($transactions->isEmpty()) {
            return false;
        }

        foreach ($transactions as $item) {
            try {
                // Obtener información del jugador
                $player = CustomerUser::getById($item->user_id, ['user_id', 'token']);

                // Verificar si el jugador existe
                if (!$player) {
                    Log::error("Jugador no encontrado para la transacción: '{$item->id}'");
                    continue;
                }

                // Realizar la transferencia de dinero
                $transactionResponse = $this->transferMoney(
                    $customer,
                    $item->game,
                    [$player->user_id, $player->token],
                    $item->bet_id,
                    $item->coin,
                    $item->amount,
                    $item->code,
                    $item->transaction_type
                );

                // Verificar si la respuesta contiene un error
                if (isset($transactionResponse->Error) && $transactionResponse->Error) {
                    Log::error("Error al procesar la transacción: '{$item->id}': {$transactionResponse->Error}");
                    continue;
                }

                if ($transactionResponse) {
                    // Actualizar la transacción si es exitosa
                    Transactions::where('id', $item->id)->update([
                        'status' => 1,
                        'processing_data' => Carbon::now(),
                        'updated_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                // Manejo de excepciones inesperadas
                Log::error("Excepción al procesar la transacción: '{$item->id}', Error: {$e->getMessage()}");
            }
        }
    }
}
