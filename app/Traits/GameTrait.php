<?php

namespace App\Traits;

use App\Models\BingoRoom;
use App\Models\CoinGameRoom;
use App\Models\ExchangeRate;
use App\Models\GameExchangeRate;
use App\Models\Games;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait GameTrait
{
    public function getTimestamp()
    {
        return Carbon::now('America/Caracas');
    }

    public function insertGameExchangeRate($room, $game, $gameMode = '')
    {
        try {
            $exchangeRates = ExchangeRate::all();
            
            $timestamp = $this->getTimestamp();

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

            GameExchangeRate::insert($recordsToInsert);
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

    public function convertValueByGame($game, $roomID, $value, $type = 'string', $coin = '')
    {
        switch ($game) {
            case 'BINGO':
                $room = BingoRoom::getRoomById($roomID, ['customer', 'status']);
                break;

            default:
                # code...
                break;
        }

        if (!$room->customer) {
            $exchangeRate = ($room->status == 2)
                ? ExchangeRate::getLastValueByCoin($coin)
                : GameExchangeRate::getGameExchangeRateValue(1, $roomID, $coin);
        }

        $amount = $value * ($exchangeRate ?? 1);

        if ($type == 'string') {
            // Formatear como cadena y eliminar ceros y comas finales
            return rtrim(number_format($amount, 2, ',', '.'), '0,');
        } else {
            // Devolver como número de punto flotante
            return (float) $amount;
        }
    }
}
