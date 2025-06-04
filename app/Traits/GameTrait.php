<?php

namespace App\Traits;

use App\Models\BingoCreator;
use App\Models\BingoRoom;
use App\Models\BingoRoomConfig;
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
        try {
            // Obtener el ID del juego
            $gameID = Games::getValueByName($game);
            $gameMode = false;

            if ($game === 'BINGO') {
                $gameMode = BingoRoomConfig::getValueByRoom($room, 'game_mode');
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

            $recordsToInsert = $coinsInGameRoom
                ->filter(function ($coin) use ($gameID, $room) {
                    // Filtrar las monedas que no tienen una tasa de cambio existente
                    return !GameExchangeRate::existsGameExchangeRate($gameID, $room, $coin);
                })
                ->map(function ($coin) use ($exchangeRates, $gameID, $room, $timestamp) {
                    // Definir tasa de cambio dependiendo de la cantidad, calcular el promedio o tomar el único valor
                    $roomExchangeRate = $exchangeRates->where('coin', $coin)->count() > 1
                        ? $exchangeRates->where('coin', $coin)->avg('exchange_rate')
                        : ($exchangeRates->where('coin', $coin)->first()->exchange_rate ?? null);

                    return [
                        'game' => $gameID,
                        'room' => $room,
                        'coin' => $coin,
                        'exchange_rate' => round($roomExchangeRate, 2),
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp
                    ];
                })->values()->toArray();

            if (empty($recordsToInsert)) return true;

            // Insertar los registros en la base de datos
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
                $room = BingoRoom::getById($roomID, ['room_type', 'status']);
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

            if ($room->room_type === 'PB') { // Sala pública
                $creatorExchangeRate = CommerceExchangeRate::whereIn('commerce', function ($query) use ($roomID) {
                    $query->select('creator_id')->from('bingo_creator')
                        ->where('creator_type', 3)->where('room', $roomID);
                })->where('status', 1)->where('coin', $coin)->avg('exchange_rate');
            }
        } else {
            $creatorExchangeRate = CustomerExchangeRate::getCustomerCoinValue($creatorID, $coin);
        }

        // Definir Tasa de Cambio de la sala de acuerdo al estatus
        $exchangeRate = ($room->status == 2)
            ? $creatorExchangeRate
            : GameExchangeRate::getGameExchangeRateValue('BINGO', $roomID, $coin);

        // Definir valor final o monto
        $amount = $data['value'] * ($exchangeRate ?? 1);

        return $this->formatAmount($amount, $type);
    }

    public function formatAmount($amount, $type)
    {
        // Truncar a 2 decimales 
        $truncated = round($amount, 2);

        if ($type === 'string') {
            // Formatear como string con separador de miles y 2 decimales
            return number_format($truncated, 2, '.', ',');
        }

        return $truncated;
    }
}
