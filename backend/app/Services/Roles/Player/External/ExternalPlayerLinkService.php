<?php

namespace App\Services\Roles\Player\External;

use App\Contracts\Roles\Player\External\ExternalPlayerRepositoryInterface;
use App\Models\Roles\Commerce;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Player\External\UserPurchaseCode;
use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use Exception;
use Illuminate\Support\Str;
use Log;

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
    ) {
    }

    /**
     * Alcance de compradores externos según el nivel de la jerarquía:
     * - Comercializadora: todos sus vendedores directos y de sus agencias.
     * - Agencia: únicamente sus vendedores.
     * - Vendedor: únicamente los compradores que él captó.
     * - Otros usuarios: ningún comprador ajeno.
     *
     * Evita que una agencia consulte compradores de agencias hermanas y que
     * vendedores de una misma red compartan datos personales sin necesidad.
     *
     * @return string[]
     */
    public function actorNetworkUserIds(int|string|null $userId) : array
    {
        if ($userId === null) {
            return [];
        }

        $userId = (string) $userId;

        $commerce = Commerce::where('user_id', $userId)->first();

        if ($commerce) {
            return $commerce->networkUserIds();
        }

        $subsidiary = Subsidiary::where('user_id', $userId)->first(['id', 'user_id']);

        if ($subsidiary) {
            return $subsidiary->networkUserIds();
        }

        $seller = Seller::where('user_id', $userId)->first(['user_id', 'subsidiary_id']);

        if (! $seller) {
            return [];
        }

        // Un vendedor adscrito a una agencia hereda el alcance de esa
        // agencia; un vendedor directo conserva acceso únicamente a sus
        // propios compradores.
        if ($seller->subsidiary_id !== null) {
            return Subsidiary::whereKey($seller->subsidiary_id)->first()?->networkUserIds() ?? [];
        }

        return [(string) $seller->user_id];
    }

    /**
     * Resuelve la URL PÚBLICA de acceso (sin cuenta) para un ExternalPlayer,
     * a partir de su `link` o su ID. $playerLink null significa jugador
     * interno (con cuenta), no requiere resolución.
     *
     * Apunta a external-players.game (ruta pública, ver
     * routes/modules/external-player.php) — NO a bingo.game, que exige
     * sesión autenticada con rol player y siempre rechazaría a un
     * ExternalPlayer sin cuenta.
     */
    public function resolveGameUrl(int|string|null $playerLink) : ?string
    {
        if ($playerLink === null) {
            return null;
        }

        // El link en sí (28 chars aleatorios) no es PII — se loguea igual que
        // cualquier identificador técnico, pero nunca los datos del jugador
        // asociado (nombre/teléfono).
        $externalPlayer = is_numeric($playerLink)
            ? ExternalPlayer::find($playerLink) ?? ExternalPlayer::where('link', $playerLink)->first()
            : ExternalPlayer::where('link', $playerLink)->first();

        if (! $externalPlayer) {
            Log::warning('ExternalPlayerLinkService: external player no encontrado', ['player_link_or_id' => $playerLink]);

            throw new Exception('Jugador externo no encontrado');
        }

        return route('external-players.game', ['link' => $externalPlayer->link]);
    }

    /**
     * Busca un ExternalPlayer existente para esta sala/teléfono; si no
     * existe, lo crea junto con su UserPurchaseCode y le asigna un link
     * nuevo. Devuelve $data con `playerLink` (el link string, para la URL
     * pública) y `externalPlayerId` (el ID real, para vincular
     * participaciones vía ExternalPlayerParticipation) seteados.
     *
     * `roomUser` es opcional: se puede llamar ANTES de generar los cartones
     * (el BingoRoomUser compartido del Commerce puede no existir todavía) —
     * external_players.room_user_id es solo informativo, ver
     * updateRoomUser().
     *
     * @param array $data Requiere: gameId, roomId, playerPhone, country,
     *                    creator, currency. Opcionales: roomUser, playerName, playerLastName.
     */
    public function resolveOrCreatePlayer(array $data) : array
    {
        $roomUser = $data['roomUser'] ?? null;
        $playerPhone = $data['playerPhone'] ?? null;

        // Solo se reutiliza un jugador captado por la MISMA red comercial: en
        // una sala PUBLIC, el mismo teléfono puede haber sido registrado por
        // otro comercio — reutilizarlo le colgaba estos cartones a su link y
        // mezclaba compradores entre comercios (external_players no tiene
        // unique por sala+teléfono: cada red lleva su propio registro).
        $player = ExternalPlayer::where('game_id', $data['gameId'])->where('room_id', $data['roomId'])
            ->where('phone', $playerPhone)
            ->whereIn('seller_user_id', $this->actorNetworkUserIds(auth()->id()))
            ->first(['id', 'link']);

        if (! $player) {
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

            if (! $purchaseCode) {
                throw new Exception('Error al crear el código de compra');
            }

            $playerLink = $this->repository->generateUniqueLink();
            $sellerUserId = auth()->id();

            // Log de diagnóstico sin PII: solo tipos/presencia de campos e IDs
            // técnicos, nunca teléfono/nombre del jugador.
            Log::info('ExternalPlayerLinkService: creando ExternalPlayer', [
                'game_id' => $data['gameId'] ?? null,
                'room_id' => $data['roomId'] ?? null,
                'seller_user_id' => $sellerUserId,
                'has_creator' => isset($data['creator']),
                'has_currency' => isset($data['currency']),
            ]);

            $dataPlayer = [
                'game_id' => (string) $data['gameId'],
                'room_id' => (string) $data['roomId'],
                'creator_user_id' => is_array($data['creator']) ? (string) ($data['creator']['id'] ?? $data['creator']) : (string) $data['creator'],
                'room_user_id' => $roomUser ? (is_array($roomUser) ? (string) ($roomUser['room_user_id'] ?? $roomUser['id'] ?? $roomUser) : (string) $roomUser) : null,
                'seller_user_id' => (string) $sellerUserId,
                'country_id' => (string) $country,
                'currency_id' => is_array($data['currency']) ? (string) ($data['currency']['id'] ?? $data['currency']) : (string) $data['currency'],
                'link' => (string) $playerLink,
                'first_name' => (string) $playerName,
                'last_name' => (string) $playerLastName,
                'identification' => null,
                'phone' => (string) $playerPhone,
                'purchase_code_id' => (string) $purchaseCode,
                'expiration_date' => now()->addDays(ExternalPlayerLinkSettings::expirationDays()),
            ];

            $player = ExternalPlayer::create($dataPlayer);

            if (! $player->id) {
                throw new Exception('Error al crear la información del jugador');
            }
        }

        $data['externalPlayerId'] = $player->id;
        $data['playerLink'] = $player->link;

        unset($data['country'], $data['playerPhone'], $data['playerName'], $data['playerLastName']);

        return $data;
    }

    /**
     * Completa/actualiza el room_user_id informativo de un ExternalPlayer
     * una vez que su BingoRoomUser ya existe — llamado después de generar
     * cartones, cuando resolveOrCreatePlayer() se invocó sin `roomUser`
     * disponible todavía.
     */
    public function updateRoomUser(int|string $externalPlayerId, int|string $roomUserId) : void
    {
        ExternalPlayer::where('id', $externalPlayerId)
            ->whereNull('room_user_id')
            ->update(['room_user_id' => (string) $roomUserId]);
    }
}
