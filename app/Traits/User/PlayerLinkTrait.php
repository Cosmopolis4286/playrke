<?php

namespace App\Traits\Users;

use App\Models\Games;
use App\Models\ExternalPlayers;
use App\Models\UserPurchaseCodes;
use Illuminate\Support\Str;

trait ExternalPlayersTrait
{
    public function createExternalPlayers($game, $room = '')
    {
        $gameID = Games::getValueByName($game);
        do {
            $code = Str::random(20);
        } while (ExternalPlayers::where('game', $gameID)->where('link', $code)->exists());
        return $code;
    }

    public function handleExternalPlayers($data)
    {
        $roomUser = $data['roomUser'] ?? null;
        $playerPhone = $data['playerPhone'] ?? null;

        $player = ExternalPlayers::where('game', 1)->where('room', $data['room'])
            ->where(function ($query) use ($playerPhone, $roomUser) {
                $query->where('phone', $playerPhone)
                    ->orWhere('id', $roomUser);
            })->value('id');

        // Verificar si el jugador no existe
        if (!$player) {

            $playerName = $data['playerName'] ?? $player->name;
            $playerLastName = $data['playerLastName'] ?? $player->lastname;
            $country = $data['country'];

            // Crear código de compra
            $purchaseCode = UserPurchaseCodes::create([
                'name' => $playerName,
                'lastname' => $playerLastName,
                'country' => $country,
                'phone' => $playerPhone,
                'code' => strtoupper(Str::random(8)),
                'status' => 'Activo',
            ])->id;

            if (!$purchaseCode) {
                throw new \Exception('Error al crear el código de compra');
            }

            $playerLink = $this->createExternalPlayers('BINGO');

            $dataPlayer = [
                'game' => Games::getValueByName('BINGO'),
                'room' => $data['room'],
                'creator_user' => $data['creator']['userId'],
                'room_user' => $roomUser,
                'seller' => $data['seller'],
                'country' => $country,
                'coin' => $data['coin'],
                'link' => $playerLink,
                'name' => $playerName,
                'lastname' => $playerLastName,
                'identification' => null,
                'phone' => $playerPhone,
                'purchase_code' => $purchaseCode
            ];

            // Crear el nuevo registro
            $player = ExternalPlayers::create($dataPlayer)->id;

            if (!$player) {
                throw new \Exception('Error al crear la información del jugador');
            }
        }

        $data['playerLink'] = $player;

        unset($data['country'], $data['playerPhone'], $data['playerName'], $data['playerLastName']);

        return $data;
    }

    public function initialCharacters($text)
    {
        // Verificar si $text es un array
        if (is_array($text)) {
            $initialCharacters = [];

            foreach ($text as $value) {
                $character = strtoupper(substr($value, 0, 1));
                $initialCharacters[] = $character;
            }
            return $initialCharacters;
        } else {
            // Si $text no es un array
            $firstCharacter = strtoupper(substr($text, 0, 1));
            return $firstCharacter;
        }
    }

    public function getGameLinkByPlayer($playerLink)
    {
        $playerLink = is_int($playerLink) ? ExternalPlayers::getValueById($playerLink, 'link') : $playerLink;
        return route('bingo.in-game', ['player' => $playerLink]);
    }
}
