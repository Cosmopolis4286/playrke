<?php

namespace App\Traits;

use App\Models\BingoCreator;
use App\Models\BingoRoom;
use App\Models\CoinGameRoom;
use App\Models\CommerceExchangeRate;
use App\Models\CustomerExchangeRate;
use App\Models\ExchangeRate;
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
            $creator = BingoCreator::getByRoom($room, ['creator_type', 'creator_id']);

            $creatortType = UserType::getValueById($creator->creator_type, 'prefix');
            $creatorID = $creator->id;
        }

        // Obtener las monedas registradas en la sala de juego
        $coinsInGameRoom = CoinGameRoom::getCoinsByRoom($gameID, $room, $gameMode);

        // Obtener ExchangeRates (Tasas de Cambio)
        if ($creatortType === 'commerce') {
            $exchangeRates = CommerceExchangeRate::getCommerceCoinValue($creatorID);
        } else if ($creatortType === 'customer') {
            $exchangeRates = CustomerExchangeRate::getCustomerCoinValue();
        }

        $timestamp = Carbon::now();

        $recordsToInsert = $coinsInGameRoom->map(function ($coin) use ($exchangeRates, $gameID, $room, $timestamp) {
            if (!GameExchangeRate::existsGameExchangeRate($gameID, $room, $coin)) {
                $averageExchangeRate = $exchangeRates->where('coin', $coin)->avg('value');
                return [
                    'game' => $gameID,
                    'room' => $room,
                    'coin' => $coin,
                    'exchange_rate' => $averageExchangeRate,
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

    public function convertToUnixTimestamp($date, $time)
    {
        // Concatenar fecha y hora en un solo string
        $dateTime = "{$date} {$time}";
        // Convertir a timestamp Unix
        return strtotime($dateTime);
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

        // Definir la tasa de cambio de acuerdo al $creator['type'] 
        $creator = $data['creator'];

        switch ($creator['type']) {
            case 'RKE';
            case 'commerce':
                $creatorExchangeRate = CommerceExchangeRate::getCommerceCoinValue($creator['id'], $coin);
                break;
            case 'customer':
                $creatorExchangeRate = CustomerExchangeRate::getValueById($coin);
                break;
            default:
                # code...
                break;
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
