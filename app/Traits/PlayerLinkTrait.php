<?php

namespace App\Traits;

use App\Models\Commerce;
use App\Models\PlayerLink;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

trait PlayerLinkTrait
{
    public function createPlayerLinkInfo($arr)
    {
        try {
            // Crear el nuevo registro
            $player = PlayerLink::create([
                'game' => $arr['game'], // Bingo
                'room' => $arr['room'], // ID Sala Bingo
                'commerce' => $arr['commerce'],
                'seller' => $arr['seller'],
                'country' => $arr['country'],
                'coin' => $arr['coin'],
                'link' => $arr['link'],
                'name' => $arr['name'],
                'lastname' => $arr['lastname'],
                'identification' => $arr['identification'],
                'phone' => $arr['phone'],
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
            $code = Str::random(16);
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

    public function getPlayerGameLink($commerceID, $PJLink, $allForOne)
    {
        $baseRoute = $this->getRouteRKE() . "bingocash/";

        $code = Commerce::getValueById($commerceID, 'code_rke') ??
            Commerce::getValueById($commerceID, 'code_uuid');

        return $baseRoute . ($allForOne == 0 ? "comm/$code/$PJLink" : $PJLink);
    }
}
