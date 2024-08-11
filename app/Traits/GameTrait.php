<?php

namespace App\Traits;

use App\Models\CoinGameRoom;
use App\Models\ExchangeRate;
use App\Models\GameExchangeRate;
use App\Models\Transactions;
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
                $averageExchangeRate = $exchangeRates->where('coin', $coin)->avg('value');
                return [
                    'game' => $game, // game == 1 Bingo
                    'room' => $room,
                    'coin' => $coin,
                    'exchange_rate' => $averageExchangeRate,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];
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
        $dateTime = $date . ' ' . $time;
        // Convertir a timestamp Unix
        $unixTimestamp = strtotime($dateTime);

        return $unixTimestamp;
    }

    public function getTransaction($game, $provider, $coin, $user)
    {
        // Obtener la marca de tiempo fuera del bucle
        $timestamp = time();

        do {
            // Concatenar los valores con una cadena aleatoria para asegurar unicidad
            $randomString = bin2hex(random_bytes(5)); // Genera una cadena aleatoria
            $data = $game . $provider . $coin . $user . $timestamp . $randomString;

            // Generar un hash MD5 del valor concatenado
            $uniqueId = md5($data);
        } while (Transactions::existsCode($uniqueId, $provider));

        return $uniqueId;
    }
}
