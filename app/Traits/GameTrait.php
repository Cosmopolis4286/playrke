<?php

namespace App\Traits;

use App\Models\BingoCreator;
use App\Models\BingoRoom;
use App\Models\CoinGameRoom;
use App\Models\CommerceExchangeRate;
use App\Models\CustomerExchangeRate;
use App\Models\GameExchangeRate;
use App\Models\Games;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait GameTrait
{
    public function insertGameExchangeRate($room, $game)
    {
        // Obtener el ID del juego
        $gameID = Games::getValueByName($game);
        $gameMode = false;

        if ($game === 'BINGO') {
            $gameMode = BingoRoom::getValueById($room, 'game_mode');
            $gameCreator = BingoCreator::getByRoom($room, ['creator_type', 'creator_id']);

            $creatortType = UserType::getValueById($gameCreator->creator_type, 'prefix');
            $creatorID = $gameCreator->creator_id;
        }

        // Obtener las monedas registradas en la sala de juego
        $coinsInGameRoom = CoinGameRoom::getCoinsByRoom($gameID, $room, $gameMode);

        // Obtener ExchangeRates (Tasas de Cambio)
        if ($creatortType === 'commerce') {
            $exchangeRates = CommerceExchangeRate::getAllExchangeRates($creatorID);
        } else if ($creatortType === 'customer') {
            $exchangeRates = CustomerExchangeRate::getAllExchangeRates($creatorID);
        }

        $timestamp = Carbon::now();

        $recordsToInsert = $coinsInGameRoom->map(function ($coin) use ($exchangeRates, $gameID, $room, $timestamp) {
            // Verificar la existencia de la tasa de cambio en la sala de juego
            if (!GameExchangeRate::existsGameExchangeRate($gameID, $room, $coin)) {

                // Definir tasa de cambio dependiendo de la cantidad, calcular el promedio o tomar el unico valor
                $roomExchangeRate = $exchangeRates->where('coin', $coin)->count() > 1
                    ? $exchangeRates->where('coin', $coin)->avg('exchange_rate')
                    : ($exchangeRates->where('coin', $coin)->first()->exchange_rate ?? null);

                return [
                    'game' => $gameID,
                    'room' => $room,
                    'coin' => $coin,
                    'exchange_rate' => $roomExchangeRate,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];
            }
        })->toArray();

        if (empty($recordsToInsert)) return true;

        try {
            GameExchangeRate::insert($recordsToInsert);
            return true;
        } catch (\Exception $e) {
            Log::error('Error al insertar en GameExchangeRate: ' . $e->getMessage(), [
                'game' => $game,
                'room' => $room,
                'exception' => $e
            ]);
            return false;
        }
    }

    public function convertToUnixTimestamp($date, $time, $timeZone = null)
    {
        // Determinar la zona horaria a usar
        $timeZone = $timeZone ?? config('app.timezone');

        // Crear un objeto Carbon con la fecha, hora y zona horaria
        return Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", $timeZone)->timestamp;
    }

    public function convertValueByGame($game, $roomID, $data, $type = 'string')
    {
        // Obtener estatus de la sala de juego
        switch ($game) {
            case 'BINGO':
                $roomStatus = BingoRoom::getValueById($roomID, 'status');
                break;
            default:
                break;
        }

        // Definir Moneda/Divisa
        $coin = $data['coin'];

        // Declarar variables del Creador de la sala
        $creatorID = $data['creator']['id'];
        $creatorType = $data['creator']['type'];

        // Definir la tasa de cambio de acuerdo al $creator['type'] 
        if (in_array($creatorType, ['RKE', 'commerce'])) {
            $creatorExchangeRate = CommerceExchangeRate::getCommerceCoinValue($creatorID, $coin);
        } else {
            $creatorExchangeRate = CustomerExchangeRate::getCustomerCoinValue($creatorID, $coin);
        }

        // Definir Tasa de Cambio de la sala de acuerdo al estatus
        $exchangeRate = ($roomStatus == 2)
            ? $creatorExchangeRate
            : GameExchangeRate::getGameExchangeRateValue('BINGO', $roomID, $coin);

        // Definir valor final o monto
        $amount = $data['value'] * ($exchangeRate ?? 1);

        return $this->formatAmount($amount, $type);
    }

    public function formatAmount($amount, $type)
    {
        // Formatear monto/valor de acuerdo al tipo
        if ($type === 'string') {
            // Formatear como cadena y eliminar ceros y comas finales
            return number_format($amount, 2, ',', '.') ?: '0,0';
        } else {
            // Devolver como número de punto flotante
            return (float) $amount;
        }
    }
}
