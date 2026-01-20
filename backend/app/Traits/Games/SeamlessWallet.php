<?php

namespace App\Traits\Games;

// Models
use App\Models\Customer;
use App\Models\CustomerGame;
use App\Models\CustomerUser;
use App\Models;
use App\Models\Transactions;
use App\Traits\Users\Roles\Customer\CustomerTrait;
// Traits

// Otros
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait SeamlessWallet
{
    use CustomerTrait;

    private function getCustomerKey($customerId)
    {
        $customer = Customer::getById($customerId, ['customer_key', 'security_key']);

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

    private function globalCurl($customerId, $dataJson, $action)
    {
        // Obtener la clave de seguridad y la ruta del enlace
        $customerKey = $this->getCustomerKey($customerId);
        $linkRoute = Customer::getValueById($customerId, 'link_route');

        // Generar la firma
        $hashHex = $this->generateSignature($dataJson, $customerKey);

        // Inicializar y configurar CURL en una sola llamada
        $curl = curl_init("{$linkRoute}/{$action}");
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

    private function globalGuzzle($customerId, $dataJson, $action)
    {
        // Validar que el JSON sea válido
        if (!is_string($dataJson) || is_null(json_decode($dataJson, true))) {
            Log::error('El parámetro $dataJson no es un JSON válido.', ['dataJson' => $dataJson]);
            return null;
        }

        // Obtener la clave de seguridad del cliente
        $customerKey = $this->getCustomerKey($customerId);
        if (!$customerKey) {
            Log::error("No se pudo obtener la clave del cliente.", ['customerId' => $customerId]);
            return null;
        }

        // Generar la firma
        $signature = $this->generateSignature($dataJson, $customerKey);

        // Obtener la URL base del cliente
        $linkRoute = Customer::getValueById($customerId, 'link_route');
        if (!$linkRoute) {
            Log::error("No se pudo obtener la URL base del cliente.", ['customerId' => $customerId]);
            return null;
        }

        // Crear una instancia de Guzzle con configuración predeterminada
        $client = $this->createGuzzleClient();

        // Configurar los encabezados de la solicitud
        $headers = [
            'X-Signature' => $signature,
            'Content-Type' => 'application/json',
        ];

        try {
            // Registrar información de la solicitud antes de enviarla
            Log::info('Enviando solicitud HTTP POST', [
                'url' => "{$linkRoute}/{$action}",
                'headers' => $headers,
                'body' => $dataJson,
            ]);

            // Realizar la solicitud HTTP POST
            $response = $client->post("{$linkRoute}/{$action}", [
                'headers' => $headers,
                'body' => $dataJson, // Usa `json` en lugar de `body`
            ]);

            // Decodificar y retornar la respuesta
            $responseData = json_decode($response->getBody()->getContents(), true);
            Log::info('Respuesta recibida', ['response' => $responseData]);

            return $responseData;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Captura errores específicos de cliente (4xx)
            Log::error("Error en la solicitud HTTP (Cliente)", [
                'message' => $e->getMessage(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            // Captura errores específicos del servidor (5xx)
            Log::error("Error en la solicitud HTTP (Servidor)", [
                'message' => $e->getMessage(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
        } catch (\Exception $e) {
            // Manejar otros errores inesperados
            Log::error("Error inesperado en la solicitud HTTP", ['message' => $e->getMessage()]);
        }

        return null; // Retornar null en caso de error
    }

    private function createGuzzleClient()
    {
        return new Client([
            'timeout' => 20,           // Tiempo máximo de espera de la respuesta en segundos
            'connect_timeout' => 20,   // Tiempo máximo de espera para la conexión en segundos
        ]);
    }

    public function getUserInfoFromCustomer($customerId, $userToken)
    {
        // Datos del cuerpo de la solicitud
        $dataJson = json_encode(["Token" => $userToken]);

        return $this->globalGuzzle($customerId, $dataJson, 'GetUserInfo');
    }

    public function getUserBalanceFromCustomer($customerId, $userToken)
    {
        // Intenta obtener el usuario localmente
        $user = CustomerUser::getByToken($customerId, $userToken, ['user_id', 'currency']);

        // Si no se encuentra, realiza una solicitud para obtener la información del usuario
        if (!$user) {
            $response = $this->getUserInfoFromCustomer($customerId, $userToken);
            // Valida si la respuesta contiene los datos esperados
            if (!isset($response['Data'])) {
                return false; // Termina si no hay datos válidos
            }
            $userId = $response['Data']['ID'];
            $currency = $response['Data']['DefaultCurrencyCode'] ?? null;
        } else {
            $userId = $user->user_id;
            $currency = $user->currency;
        }

        // Construye el cuerpo del JSON de manera segura
        $dataJson = json_encode([
            "Token" => $userToken,
            "UserID" => $userId,
            "CurrencyCode" => $currency ?: ''
        ]);

        // Realiza la solicitud y retorna la respuesta
        return $this->globalGuzzle($customerId, $dataJson, 'GetBalance');
    }

    public function transferMoney($transaction, $additionalData = '')
    {
        // Obtener datos del cliente
        $customerId = $transaction->customer;
        $customer = Customer::getById($customerId, ['brand', 'product']);

        // Validar que se obtuvieron datos del cliente
        if (!$customer) {
            Log::error("Cliente no encontrado: [ID => {$customerId}]");
            return ['Error' => 'Cliente no encontrado'];
        }

        // Obtener Info del Juego con respecto al cliente
        $game = CustomerGame::getByGameID($customerId, $transaction->game, ['customer_game_id', 'product']);

        if (!$game) {
            Log::error("No existe el ID de Juego del cliente [{$customerId}] para el juego [games.id={$transaction->game}]");
            return ['Error' => "No existe el ID de Juego del cliente [{$customerId}]"];
        }

        // Obtener informacion del jugador
        $player = CustomerUser::getById($transaction->user_id, ['user_id', 'brand', 'token', 'currency']);

        // Datos del cuerpo de la solicitud
        $dataJson = json_encode([
            "Ticks" => "0000000",
            "Game" => $game->customer_game_id,
            "Product" => $game->product,
            "Brand" => $player->brand,
            "Token" => $player->token ?? '',
            "UserID" => $player->user_id ?? '',
            "CurrencyCode" => $player->currency,
            "BetID" => $transaction->bet_id,
            "Amount" => $transaction->amount,
            "TransactionID" => $transaction->code,
            "TransactionType" => $transaction->transaction_type,
            "AdditionalData" => json_encode($additionalData) ?? "{}"
        ]);

        // Llamada al método globalGuzzle
        $response = $this->globalGuzzle($customerId, $dataJson, 'TransferMoney');

        // Manejo de la respuesta
        if (!$response || (isset($response['Error']) && $response['Error'])) {
            Log::error("Error en la transferencia de dinero: " . ($response['Error'] ?? 'Error desconocido'));
            return ['Error' => ':' . $response['Error'] ?? ''];
        }

        return $response;
    }

    public function getTransaction($game, $customerId, $user)
    {
        // Obtener la marca de tiempo fuera del bucle
        $timestamp = Carbon::now()->timestamp;

        do {
            // Concatenar los valores con una cadena aleatoria para asegurar unicidad
            $randomString = bin2hex(random_bytes(5)); // Genera una cadena aleatoria
            $data = $game . $customerId . $user . $timestamp . $randomString;

            // Generar un hash MD5 del valor concatenado
            $uniqueId = md5($data);
        } while (Transaction::existsCode($uniqueId, $customerId));

        return $uniqueId;
    }

    // Funcion para manejar una transaccion tipo "BET"
    private function handleTransactionByBET(array $game, $customer, $roomId, $playerUser, $currency, $totalSaleAmount)
    {
        try {
            // Manejo de transacciones en un closure
            DB::transaction(function () use ($game, $customer, $roomId, $playerUser, $currency, $totalSaleAmount) {
                // Registrar la transacción
                $transaction = $this->registerTransaction(
                    $game,
                    $customer,
                    $playerUser,
                    $roomId,
                    'BET',
                    $currency,
                    $totalSaleAmount
                );

                // Validación de la transacción
                if (!$transaction) {
                    throw new \Exception('Error al registrar la transacción');
                }

                // Procesar la transferencia de dinero [enviar datos de la transaccion]
                $transferResponse = $this->transferMoney($transaction);

                // Validación de respuesta de transferencia
                if (isset($transferResponse['Error'])) {
                    throw new \Exception($transferResponse['Error']);
                }

                // Actualizar la transacción como exitosa
                $transaction->update([
                    'status' => 1,
                    'processing_data' => now(),
                    'updated_at' => now(),
                ]);

                return $transaction;
            });
        } catch (\Exception $e) {
            // Registrar el error y devolverlo como respuesta
            Log::error("Error en handleTransactionByBET ['customerId' => $customer]: " . $e->getMessage());
            return ['Error' => $e->getMessage()];
        }
    }

    // Función para registrar las transacciones del cliente
    public function registerTransaction(array $game, $customer, $clientUser, $betId, $type, $currency, $amount, $status = 0)
    {
        try {
            // Validar datos requeridos
            if (empty($game['name']) || empty($game['game_mode'])) {
                throw new \InvalidArgumentException('El juego debe tener un nombre y un modo válido.');
            }

            // Obtener valores necesarios
            $gameId = Game::getValueByName($game['name']);
            $code = $this->getTransaction($game['name'], $customer, $clientUser);

            // Crear la transacción
            return Transaction::create([
                'customer' => $customer,
                'user_id' => $clientUser,
                'game' => $gameId,
                'game_mode' => $game['game_mode'],
                'bet_id' => $betId,
                'code' => $code, // transaction_id
                'transaction_type' => $type,
                'currency' => $currency,
                'amount' => $amount,
                'status' => $status,
            ]);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Datos inválidos para registrar la transacción: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error("Error al registrar la transacción ['customerId' => $customer]: " . $e->getMessage());
            return false;
        }
    }
}
