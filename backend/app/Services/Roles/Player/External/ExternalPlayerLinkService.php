<?php

namespace App\Services\Roles\Player\External;

use App\Contracts\Roles\Player\External\ExternalPlayerRepositoryInterface;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Player\External\UserPurchaseCode;
use Illuminate\Support\Str;

/**
 * Ciclo de vida del link de un ExternalPlayer (comprador de cartones sin
 * cuenta en la plataforma, captado por un vendedor): generarlo, crear el
 * registro asociado (con su UserPurchaseCode), y resolverlo de vuelta a una
 * sala de juego.
 *
 * Antes vivía como App\Traits\Users\PlayerLinkTrait — nombre engañoso (sin
 * relación con el jugador registrado `User`+rol `player`), mezclado por
 * `use` en BingoCardGenerateService con un solo consumidor real (no
 * justificaba un trait) y con initialCharacters(), un helper de formateo de
 * texto sin ninguna relación con jugadores externos ni con links (código
 * muerto, sin consumidores, descartado en esta migración).
 */
final class ExternalPlayerLinkService
{
    public function __construct(
        private readonly ExternalPlayerRepositoryInterface $repository,
    ) {}

    /**
     * Busca un ExternalPlayer existente para esta sala/teléfono; si no
     * existe, lo crea junto con su UserPurchaseCode y le asigna un link
     * nuevo. Devuelve $data con `playerLink` seteado al ID del jugador
     * externo (nuevo o encontrado).
     *
     * @param array $data Requiere: gameId, roomId, roomUser, playerPhone,
     *   country, creator, currency. Opcionales: playerName, playerLastName.
     */
    public function resolveOrCreatePlayer(array $data): array
    {
        $roomUser = $data['roomUser'] ?? null;
        $playerPhone = $data['playerPhone'] ?? null;

        $player = ExternalPlayer::where('game_id', $data['gameId'])->where('room_id', $data['roomId'])
            ->where(function ($query) use ($playerPhone, $roomUser) {
                $query->where('phone', $playerPhone)
                    ->orWhere('id', $roomUser);
            })->value('id');

        if (!$player) {
            $playerName = $data['playerName'] ?? null;
            $playerLastName = $data['playerLastName'] ?? null;
            $country = $data['country'];

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

            $playerLink = $this->repository->generateUniqueLink();
            $sellerUserId = auth()->id();

            // Log de diagnóstico sin PII: solo tipos/presencia de campos e IDs
            // técnicos, nunca teléfono/nombre del jugador.
            \Log::info('ExternalPlayerLinkService: creando ExternalPlayer', [
                'game_id' => $data['gameId'] ?? null,
                'room_id' => $data['roomId'] ?? null,
                'seller_user_id' => $sellerUserId,
                'has_creator' => isset($data['creator']),
                'has_currency' => isset($data['currency']),
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
                'expiration_date' => now()->addDays(ExternalPlayerLinkSettings::expirationDays()),
            ];

            $player = ExternalPlayer::create($dataPlayer)->id;

            if (!$player) {
                throw new \Exception('Error al crear la información del jugador');
            }
        }

        $data['playerLink'] = $player;

        unset($data['country'], $data['playerPhone'], $data['playerName'], $data['playerLastName']);

        return $data;
    }

    /**
     * Resuelve la URL de la sala de Bingo asociada a un link/ID de
     * ExternalPlayer. $playerLink null significa jugador interno (con
     * cuenta), no requiere resolución.
     */
    public function resolveGameUrl($playerLink): ?string
    {
        if ($playerLink === null) {
            return null;
        }

        if (is_int($playerLink)) {
            $playerLink = ExternalPlayer::getValueById($playerLink, 'link');
        }

        // El link en sí (28 chars aleatorios) no es PII — se loguea igual que
        // cualquier identificador técnico, pero nunca los datos del jugador
        // asociado (nombre/teléfono).
        $externalPlayer = ExternalPlayer::where('link', $playerLink)->first();

        if (!$externalPlayer) {
            \Log::warning('ExternalPlayerLinkService: external player no encontrado por link', ['link' => $playerLink]);

            if (is_numeric($playerLink)) {
                $externalPlayer = ExternalPlayer::find($playerLink);
            }

            if (!$externalPlayer) {
                throw new \Exception('Jugador externo no encontrado');
            }
        }

        $roomId = $externalPlayer->room_id;

        if (!$roomId) {
            \Log::error('ExternalPlayerLinkService: room_id nulo para external player', ['external_player_id' => $externalPlayer->id]);
            throw new \Exception('ID de sala no encontrado para el jugador externo');
        }

        return route('bingo.game', ['room' => $roomId]);
    }
}
