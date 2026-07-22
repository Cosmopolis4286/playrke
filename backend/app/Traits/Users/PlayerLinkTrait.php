<?php

namespace App\Traits\Users;

use App\Contracts\Roles\Player\External\ExternalPlayerRepositoryInterface;
use App\Models\Game;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Player\External\UserPurchaseCode;
use Illuminate\Support\Str;

trait PlayerLinkTrait
{
    /**
     * Crear un enlace único para un jugador externo.
     *
     * Delega en ExternalPlayerRepository::generateUniqueLink() — antes
     * duplicaba aquí la misma lógica con una longitud distinta (28 vs 20
     * caracteres del repositorio, que además nunca estaba bindeado), dos
     * generadores inconsistentes para el mismo campo `link`.
     *
     * @param string $gameId The game ID
     * @param string $roomId The room ID (sin uso — la unicidad del link es
     *   global por diseño de la columna `external_players.link`, no por juego)
     * @return string El enlace creado
     */
    public function createExternalPlayers(string $gameId, string $roomId = '')
    {
        return app(ExternalPlayerRepositoryInterface::class)->generateUniqueLink();
    }

    /**
     * Manejar jugadores externos
     * 
     * @param array $data Los datos a manejar
     * @return void
     */
    public function handleExternalPlayers($data)
    {
        $roomUser = $data['roomUser'] ?? null;
        $playerPhone = $data['playerPhone'] ?? null;

        $player = ExternalPlayer::where('game_id', $data['gameId'])->where('room_id', $data['roomId'])
            ->where(function ($query) use ($playerPhone, $roomUser) {
                $query->where('phone', $playerPhone)
                    ->orWhere('id', $roomUser);
            })->value('id');

        // Verificar si el jugador no existe
        if (!$player) {

            $playerName = $data['playerName'] ?? $player->first_name;
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

            $playerLink = $this->createExternalPlayers($data['gameId'], $data['roomId']);

            // Get the authenticated user ID for seller_user_id
            $sellerUserId = auth()->id();

            // Debug: Log the values to understand what's causing the issue
            \Log::info('PlayerLinkTrait Debug', [
                'roomUser' => $roomUser,
                'roomUser_type' => gettype($roomUser),
                'creator' => $data['creator'] ?? 'missing',
                'creator_type' => gettype($data['creator'] ?? 'missing'),
                'seller' => $data['seller'] ?? 'missing',
                'seller_type' => gettype($data['seller'] ?? 'missing'),
                'seller_user_id' => $sellerUserId,
                'currency' => $data['currency'] ?? 'missing',
                'currency_type' => gettype($data['currency'] ?? 'missing'),
            ]);

            $dataPlayer = [
                'game_id' => (string)$data['gameId'],
                'room_id' => (string)$data['roomId'],
                'creator_user_id' => is_array($data['creator']) ? (string)($data['creator']['id'] ?? $data['creator']) : (string)$data['creator'],
                'room_user_id' => is_array($roomUser) ? (string)($roomUser['room_user_id'] ?? $roomUser['id'] ?? $roomUser) : (string)$roomUser,
                'seller_user_id' => (string)$sellerUserId,
                'country_id' => (string)$country,
                'currency_id' => is_array($data['currency']) ? (string)($data['currency']['id'] ?? $data['currency']) : (string)$data['currency'],
                'link' => (string)$playerLink,
                'first_name' => (string)$playerName,
                'last_name' => (string)$playerLastName,
                'identification' => null,
                'phone' => (string)$playerPhone,
                'purchase_code_id' => (string)$purchaseCode,
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
        // If playerLink is null, it's an internal player, return null
        if ($playerLink === null) {
            \Log::info('Internal player detected, no external player link needed');
            return null;
        }

        // If playerLink is an integer, it's the external player ID, get the link
        if (is_int($playerLink)) {
            $playerLink = ExternalPlayer::getValueById($playerLink, 'link');
        }

        // Debug: Log the player link and search
        \Log::info('getGameLinkByPlayer Debug', [
            'playerLink' => $playerLink,
            'playerLink_type' => gettype($playerLink)
        ]);

        // Get the external player record to get the room_id
        $externalPlayer = ExternalPlayer::where('link', $playerLink)->first();

        if (!$externalPlayer) {
            \Log::error('External player not found for link', ['playerLink' => $playerLink]);

            // Try to find by ID as fallback
            if (is_numeric($playerLink)) {
                $externalPlayer = ExternalPlayer::find($playerLink);
                \Log::info('Trying to find by ID as fallback', [
                    'playerLink' => $playerLink,
                    'found' => $externalPlayer ? true : false
                ]);
            }

            if (!$externalPlayer) {
                throw new \Exception('Jugador externo no encontrado');
            }
        }

        $roomId = $externalPlayer->room_id;

        if (!$roomId) {
            \Log::error('Room ID is null for external player', [
                'playerLink' => $playerLink,
                'externalPlayer' => $externalPlayer->toArray()
            ]);
            throw new \Exception('ID de sala no encontrado para el jugador externo');
        }

        \Log::info('Generating game link', [
            'playerLink' => $playerLink,
            'roomId' => $roomId,
            'route' => 'bingo.game',
            'externalPlayer_id' => $externalPlayer->id,
            'externalPlayer_link' => $externalPlayer->link
        ]);

        return route('bingo.game', ['room' => $roomId]);
    }
}
