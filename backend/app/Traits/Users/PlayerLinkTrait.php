<?php

namespace App\Traits\Users;

use App\Models\Game;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Player\External\UserPurchaseCode;
use Illuminate\Support\Str;

trait PlayerLinkTrait
{
    public function createExternalPlayers($game, $room = '')
    {
        $gameId = Game::getValueByName($game, 'id');
        do {
            $code = Str::random(20);
        } while (ExternalPlayer::where('game_id', $gameId)->where('link', $code)->exists());
        return $code;
    }

    public function handleExternalPlayers($data)
    {
        $roomUser = $data['roomUser'] ?? null;
        $playerPhone = $data['playerPhone'] ?? null;

        $player = ExternalPlayer::where('game_id', 1)->where('room_id', $data['room'])
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
            // Limpiar teléfono para que quepa en 14 caracteres (eliminar espacios y guiones)
            $cleanPhone = preg_replace('/[\s\-]/', '', $playerPhone);
            if (strlen($cleanPhone) > 14) {
                $cleanPhone = substr($cleanPhone, 0, 14);
            }
            
            $purchaseCode = UserPurchaseCode::create([
                'first_name' => $playerName,
                'last_name' => $playerLastName,
                'country_id' => $country,
                'phone' => $cleanPhone,
                'purchase_code' => strtoupper(Str::random(8)),
                'status' => 'ACTIVE',
            ])->id;

            if (!$purchaseCode) {
                throw new \Exception('Error al crear el código de compra');
            }

            $playerLink = $this->createExternalPlayers('BINGO');

            $dataPlayer = [
                'game_id' => Game::getValueByName('BINGO', 'id'),
                'room_id' => $data['room'],
                'creator_user_id' => $data['creator']['id'],
                'room_user_id' => $roomUser,
                'seller_user_id' => $data['seller'],
                'country_id' => $country,
                'currency_id' => $data['currency'],
                'link' => $playerLink,
                'first_name' => $playerName,
                'last_name' => $playerLastName,
                'identification' => null,
                'phone' => $playerPhone,
                'purchase_code_id' => $purchaseCode,
                'expiration_date' => now()->addDays(7),
            ];

            // Crear el nuevo registro
            $player = ExternalPlayer::create($dataPlayer)->id;

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
        $playerLink = is_int($playerLink) ? ExternalPlayer::getValueById($playerLink, 'link') : $playerLink;
        return route('bingo.in-game', ['player' => $playerLink]);
    }
}
