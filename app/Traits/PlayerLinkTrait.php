<?php

namespace App\Traits;

use App\Models\Games;
use App\Models\PlayerLink;
use App\Models\UserPurchaseCodes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

trait PlayerLinkTrait
{
    public function createPlayerLinkInfo($arr)
    {
        try {

            $country = $arr['country'];
            $name = $arr['name'];
            $lastname = $arr['lastname'];
            $phone = $arr['phone'];

            // Crear código de compra
            $purchaseCode = UserPurchaseCodes::create([
                'name' => $name,
                'lastname' => $lastname,
                'country' => $country,
                'phone' => $phone,
                'code' => strtoupper(Str::random(8)),
                'status' => 'Activo',
            ]);

            if (!$purchaseCode) {
                throw new \Exception('Error al crear el código de compra');
            }

            // Crear el nuevo registro
            $player = PlayerLink::create([
                'game' => $arr['game'], // Bingo
                'room' => $arr['room'], // ID Sala Bingo
                'creator_user' => $arr['creator_user'],
                'seller' => $arr['seller'],
                'country' => $country,
                'coin' => $arr['coin'],
                'link' => $arr['link'],
                'name' => $name,
                'lastname' => $lastname,
                'identification' => $arr['identification'],
                'phone' => $phone,
                'purchase_code' => $purchaseCode->id,
            ]);

            if (!$player) {
                throw new \Exception('Error al crear el jugador');
            }

            return $player->id;
        } catch (\Exception $e) {
            Log::error('Error en createPlayerLinkInfo(): ' . $e->getMessage());
            return false; // Retornar false si algo falla
        }
    }

    public function createPlayerLink($game, $room = '')
    {
        $gameID = Games::getValueByName($game);
        do {
            $code = Str::random(20);
        } while (PlayerLink::where('game', $gameID)->where('link', $code)->exists());
        return $code;
    }

    public function handlePlayerLink($data)
    {
        $playerLink = $data['playerLink'] ?? null;
        $playerPhone = $data['playerPhone'] ?? null;

        $player = PlayerLink::select('id', 'name', 'lastname', 'link')
            ->where('game', 1)->where('room', $data['room'])
            ->where(function ($query) use ($playerPhone, $playerLink) {
                $query->where('phone', $playerPhone)
                    ->orWhere('id', $playerLink);
            })->first();

        $playerName = $data['playerName'] ?? $player->name;
        $playerLastName = $data['playerLastName'] ?? $player->lastname;

               // Verificar si el jugador no existe
        if (!$player) {
            $playerLink = $this->createPlayerLink('BINGO');

            $arrDataPlayer = [
                'game' => Games::getValueByName('BINGO'),
                'room' => $data['room'],
                'creator_user' => $data['creator']['userId'],
                'room_user' => $data['roomUser'],
                'seller' => $data['seller'],
                'country' => $data['country'],
                'coin' => $data['coin'],
                'link' => $playerLink,
                'name' => $playerName,
                'lastname' => $playerLastName,
                'identification' => null,
                'phone' => $playerPhone,
            ];

            $player = $this->createPlayerLinkInfo($arrDataPlayer);

            if (!$player) {
                throw new \Exception('Error al crear la información del jugador');
            }
        } else {
            $player = $player->id ?? null;
        }

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
        $playerLink = is_int($playerLink) ? PlayerLink::getValueById($playerLink, 'link') : $playerLink;
        return route('bingo.in-game', ['player' => $playerLink]);
    }
}
