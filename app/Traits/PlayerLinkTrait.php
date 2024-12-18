<?php

namespace App\Traits;

use App\Models\Commerce;
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

            // Crear codigo de compra 
            $purchaseCode = UserPurchaseCodes::create([
                'name' => $name,
                'lastname' => $lastname,
                'country' => $country,
                'phone' => $phone,
                'code' => strtoupper(Str::random(8)),
                'status' => 'Activo'
            ]);

            // Crear el nuevo registro
            $player = PlayerLink::create([
                'game' => $arr['game'], // Bingo
                'room' => $arr['room'], // ID Sala Bingo
                'commerce' => $arr['commerce'],
                'seller' => $arr['seller'],
                'country' => $country,
                'coin' => $arr['coin'],
                'link' => $arr['link'],
                'name' => $name,
                'lastname' => $lastname,
                'identification' => $arr['identification'],
                'phone' => $phone,
                'purchase_code' => $purchaseCode->id
            ]);

            return $player->id;
        } catch (\Exception $e) {
            // Manejo de errores
            Log::error('Error al crear la información del jugador: ' . $e->getMessage());
            return false;
        }
    }

    public function createPlayerLink($game, $room = '')
    {
        do {
            $code = Str::random(18);
        } while (PlayerLink::where('game', $game)->where('link', $code)->exists());
        return $code;
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

    public function getGameLinkByPlayer($commerceID, $playerLink, $roomType)
    {
        if ($roomType == 1) {
            $commerceCode = Commerce::getValueById($commerceID, 'code_uuid');
            return route('bingocash.in-game-commerce', ['commerce' => $commerceCode, 'player' => $playerLink]);
        } else {
            return route('bingocash.in-game-rke', ['player' => $playerLink]);
        }
    }
}
