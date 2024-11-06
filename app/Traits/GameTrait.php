<?php

namespace App\Traits;

use App\Models\BingoRoom;
use App\Models\CoinGameRoom;
use App\Models\CommerceExchangeRate;
use App\Models\CustomerExchangeRate;
use App\Models\ExchangeRate;
use App\Models\GameExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait GameTrait
{
    public function insertGameExchangeRate($room, $game, $gameMode = '')
    {
        $exchangeRates = ExchangeRate::all();

        $timestamp = Carbon::now();

        $coins = $exchangeRates->pluck('coin')->unique();

        $query = CoinGameRoom::where('game', $game)->where('room', $room)->whereIn('coin', $coins);

        if ($gameMode) $query->where('game_mode', $gameMode);

        $coinsInGame = $query->pluck('coin');

        $recordsToInsert = $coinsInGame->map(function ($coin) use ($exchangeRates, $game, $room, $timestamp) {
            if (!GameExchangeRate::existsGameExchangeRate($game, $room, $coin)) {
                $averageExchangeRate = $exchangeRates->where('coin', $coin)->avg('value');
                return [
                    'game' => $game, // game == 1 Bingo
                    'room' => $room,
                    'coin' => $coin,
                    'exchange_rate' => $averageExchangeRate,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];
            }
        })->toArray();

        try {
            if (!empty($recordsToInsert)) GameExchangeRate::insert($recordsToInsert);
            return true;
        } catch (\Throwable $th) {
            Log::error('Error al insertar en GameExchangeRate: ' . $th->getMessage(), [
                'room' => $room,
                'exception' => $th
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
                $creatorExchangeRate = CustomerExchangeRate::getCustomerCoinValue($creator['id'], $coin);
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

        if ($type === 'string') {
            // Formatear como cadena y eliminar ceros y comas finales
            return rtrim(number_format($amount, 2, ',', '.'), '0,');
        } else {
            // Devolver como número de punto flotante
            return (float) $amount;
        }
    }
}
