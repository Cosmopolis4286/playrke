<?php

namespace App\Domains\Games\Bingo\Services\Card\Generate;

use App\Domains\Games\Bingo\DTOs\Card\Generate\{BingoCardGenerateContexDTO, BingoCardGenerateDTO};
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Domains\Games\Bingo\Events\BingoCardSold;
use App\Domains\Games\Bingo\Models\Card\CardPrice\BingoCardPrice;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Services\Card\BingoCardPriceCalculator;
use App\Domains\Games\Bingo\Services\Card\BingoCardGenerationService;
use App\Services\WalletService;
use App\Traits\Users\PlayerLinkTrait;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Game;

/**
 * Servicio para la generación de cartones de Bingo.
 *
 */
final class BingoCardGenerateService
{
    use PlayerLinkTrait;

    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly \App\Services\Roles\RoleUserService $roleUserService,
        private readonly \App\Services\Roles\RoleEntityResolver $roleEntityResolver,
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencies,
        private readonly \App\Contracts\Roles\Commerce\CommerceRepositoryInterface $commerceRepository,
        private readonly \App\Contracts\Roles\Commerce\CommerceCurrencyRepositoryInterface $commerceCurrencyRepository,
        private readonly \App\Contracts\Roles\Seller\SellerRepositoryInterface $sellerRepository,
        private readonly \App\Contracts\Roles\Seller\SellerCurrencyRepositoryInterface $sellerCurrencyRepository,
        private readonly \App\Contracts\Games\Common\GameMode\GameModeRepositoryInterface $gameModes,
        private readonly \App\Contracts\Games\Common\Room\RoomTypeRepositoryInterface $roomTypes,
        private readonly \App\Contracts\Games\Common\PrizePool\PrizePoolTypeRepositoryInterface $prizePoolTypes,
        private readonly BingoCardPriceCalculator $priceCalculator,
        private readonly \App\Contracts\WalletRepositoryInterface $walletRepository,
        private readonly WalletService $walletService,
        private readonly \App\Domains\Games\Bingo\Services\Card\BingoCardGenerationService $cardGenerationService,
        private readonly \App\Services\ExchangeRate\CurrencyExchangeRateService $currencyExchangeRate,
        private readonly \App\Services\ExchangeRate\ExchangeRateDomainService $exchangeRateDomain,
    ) {}

    /**
     * Resolver contexto la vista de generar cartones de bingo.
     *
     * @param int $userId ID del usuario
     * @return BingoCardGenerateContexDTO
     *
     * @throws DomainException Si no se puede resolver el contexto
     */
    public function viewContext(int $userId): BingoCardGenerateContexDTO
    {
        return new BingoCardGenerateContexDTO(
            gameModes: $this->gameModes->getActiveGameModes(),
            roomTypes: $this->roomTypes->getActiveTypes(),
            prizePoolTypes: $this->prizePoolTypes->getActiveTypes(),
        );
    }

    /**
     * Obtiene lista de salas de bingo filtradas.
     *
     * Reglas:
     * - Solo salas activas
     * - Del creador o públicas
     * - Filtrado progresivo por date, roomType y gameMode
     *
     * Estrategia:
     * - date obligatorio
     * - roomType opcional (valida existencia)
     * - gameMode opcional (si viene, trae data completa)
     *
     * @param array{
     *     date?: string|null,
     *     roomType?: string|null,
     *     gameMode?: string|null
     * } $filters
     *
     * @return array{
     *     exists: bool,
     *     rooms: array<int, array<string, mixed>>
     * }
     */
    public function fetchRoom(array $filters): array
    {
        $date          = $filters['date']          ?? null;
        $roomType      = $filters['roomType']      ?? null;
        $gameMode      = $filters['gameMode']      ?? null;
        $prizePoolType = $filters['prizePoolType'] ?? null;

        if (!$date) {
            return $this->emptyResponse();
        }

        $creatorUserId = $this->resolveCreatorOrManagerUserId();

        $query = BingoRoom::query()
            ->active()
            ->forDate($date)
            ->accessibleBy($creatorUserId)
            ->filterRoomType($roomType)
            ->filterGameMode($gameMode)
            ->filterPrizePoolType($prizePoolType);

        // Si no hay gameMode → solo validar existencia
        if (!$gameMode) {
            return [
                'exists' => $query->exists(),
                'rooms'  => []
            ];
        }

        $rooms = $query
            ->select([
                'bingo_rooms.id',
                'bingo_rooms.uuid_code',
                'bingo_rooms.prize_pool',
                'bingo_rooms.total_players',
                'bingo_rooms.creator_user_id',
            ])
            ->join('bingo_rooms_config', 'bingo_rooms_config.room_id', '=', 'bingo_rooms.id')
            ->with([
                'config:id,room_id,room_type_id,game_mode_id,card_price_usd,date,time'
            ])
            ->orderBy('bingo_rooms_config.time')
            ->get()
            ->map(fn($room) => [
                'id'              => $room->hashed_id,
                'uuid_code'       => $room->uuid_code,
                'prize_pool'      => $room->prize_pool,
                'total_players'   => $room->total_players,
                'creator_user_id' => $room->creator_user_id,
                'config'          => $room->config,
            ])
            ->toArray();

        return [
            'exists' => !empty($rooms),
            'rooms'  => $rooms
        ];
    }

    /**
     * Obtener países disponibles para el usuario según su rol y la sala de bingo.
     *
     * Lógica de intersección:
     * 1. Monedas activas de la entidad del usuario (commerce o seller).
     * 2. Si el usuario es seller, además intersecta con las monedas activas
     *    de su manager (commerce padre), ya que el seller opera dentro del
     *    catálogo del commerce que lo gestiona.
     * 3. Monedas permitidas en la sala (campo JSON `allowed_currencies`
     *    en `bingo_rooms_config`).
     *
     * El resultado son los países cuya moneda queda en la intersección final.
     *
     * @param string $roomId ID de la sala de bingo
     * @param string $userId ID del usuario autenticado (puede ser commerce o seller)
     * @return array{countries: \Illuminate\Database\Eloquent\Collection} Países disponibles con id, name e iso_code_2
     *
     * @throws DomainException Si el rol no tiene soporte de monedas
     */
    public function getAvailableCountries(string $roomId, string $userId): array
    {
        // Determinar el prefijo de rol activo del usuario (ej. 'commerce', 'seller')
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        // Monedas explícitamente permitidas en la sala (JSON nullable → fallback a [])
        $allowedCurrenciesRoom = BingoRoomConfig::where('room_id', $roomId)->value('allowed_currencies') ?? [];
        $allowedCurrenciesRoom = array_map('strval', $allowedCurrenciesRoom);

        // superadmin / admin: ven todos los países de las monedas permitidas en la sala
        if (in_array($role, ['superadmin', 'admin'], true)) {
            $countriesIdsAvailable = $this->currencies->getCountriesByCurrencies($allowedCurrenciesRoom);
            $countries = $this->countries->getByIds($countriesIdsAvailable, ['id', 'name', 'iso_code_2']);
            return ['countries' => $countries];
        }

        // Obtener el ID de la entidad (commerce_id, seller_id, etc.) asociada al usuario
        $entityId = $this->roleEntityResolver->resolveEntityId($userId, $role);

        // Monedas activas habilitadas para esta entidad según su rol
        $entityCurrencies = $this->resolveEntityCurrencies($entityId, $role);

        // Para sellers: también se requiere la intersección con las monedas
        // del commerce que los gestiona (manager_user_id → commerce).
        // Esto evita que un seller ofrezca monedas que su commerce no opera.
        $managerCurrencies = null;
        if ($role === 'seller') {
            // manager_user_id es el user_id del commerce padre del seller
            $managerUserId   = $this->resolveCreatorOrManagerUserId();
            $managerRole     = $this->roleUserService->getActiveUserRolePrefix($managerUserId);
            $managerEntityId = $this->roleEntityResolver->resolveEntityId($managerUserId, $managerRole);

            // Monedas activas del commerce manager
            $managerCurrencies = $this->resolveEntityCurrencies($managerEntityId, $managerRole);
        }

        // Construir los conjuntos a intersectar (todos como strings para evitar
        // comparaciones fallidas por tipo int vs string en los IDs)
        $sets = [
            array_map('strval', $entityCurrencies),
            $allowedCurrenciesRoom,
        ];

        if ($managerCurrencies !== null) {
            $sets[] = array_map('strval', $managerCurrencies);
        }

        // Monedas que pasan los tres filtros: entidad + sala [+ manager si es seller]
        $currenciesAllowedInTheRoom = array_values(array_intersect(...$sets));

        // Obtener los country_id de cada moneda resultante
        $countriesIdsAvailable = $this->currencies->getCountriesByCurrencies($currenciesAllowedInTheRoom);

        // Hidratar los modelos de país con los campos mínimos necesarios para la vista
        $countries = $this->countries->getByIds($countriesIdsAvailable, ['id', 'name', 'iso_code_2']);

        return ['countries' => $countries];
    }

    /**
     * Resuelve las monedas activas disponibles para una entidad según su rol.
     *
     * Centraliza el despacho a los repositorios correctos para no duplicar
     * el bloque match en `getAvailableCountries` (usado tanto para la entidad
     * propia como para el manager del seller).
     *
     * @param string $entityId ID de la entidad (commerce_id o seller_id)
     * @param string $role     Prefijo de rol ('commerce' | 'seller')
     * @return array<int, string> Array de IDs de monedas activas
     *
     * @throws DomainException Si el rol no tiene soporte de monedas implementado
     */
    private function resolveEntityCurrencies(string $entityId, string $role): array
    {
        return match ($role) {
            // getAvailableCurrenciesIds filtra por status = 'ACTIVE' internamente
            'commerce' => $this->commerceCurrencyRepository->getAvailableCurrenciesIds($entityId),
            // segundo parámetro true = solo relaciones activas (status = 1)
            'seller'   => $this->sellerCurrencyRepository->getAvailableCurrencies($entityId, true),
            default    => throw new DomainException('Rol sin soporte de monedas: ' . $role),
        };
    }

    /**
     * Obtiene el precio de un cartón para una sala y país específicos.
     *
     * Lógica:
     * 1. Resuelve el rol y la entidad del usuario autenticado.
     * 2. Deriva el commerce responsable (propio si es commerce, del manager si es seller).
     * 3. Obtiene la moneda del país y la tasa de cambio del commerce.
     * 4. Busca precio personalizado en bingo_card_prices; si no existe, usa precio base USD × tasa.
     * 5. Retorna comisión de la entidad autenticada (commerce.commission o seller.commission).
     *
     * @param string $roomId    ID real de la sala de bingo (decodificado por route model binding).
     * @param string $userId    ID del usuario autenticado.
     * @param string $countryId ID del país seleccionado (Snowflake como string).
     *
     * @return array{
     *     card_price: float,
     *     currency_symbol: string,
     *     exchange_rate: float,
     *     is_custom_price: bool,
     *     seller_commission: float
     * }
     *
     * @throws DomainException Si la moneda, el commerce o la tasa no se encuentran.
     */
    public function getCardPrice(string $roomId, string $userId, string $countryId): array
    {
        // 1. Rol y entidad del usuario autenticado
        $role     = $this->roleUserService->getActiveUserRolePrefix($userId);

        // superadmin / admin: precio base de la sala en USD (sin entidad ni tasa)
        if (in_array($role, ['superadmin', 'admin'], true)) {
            $currencySymbol = $this->currencies->getValueByCountry($countryId, 'symbol');
            $room           = BingoRoom::select('id')->with('config:id,room_id,card_price_usd')->find($roomId);
            $cardPrice      = $room?->config?->card_price_usd ?? 0;

            return [
                'card_price'        => (float) $cardPrice,
                'currency_symbol'   => (string) ($currencySymbol ?? 'USD'),
                'exchange_rate'     => 1.0,
                'is_custom_price'   => false,
                'seller_commission' => 0.0,
            ];
        }

        // 2. Commerce responsable y user_id de su creador (para filtrar bingo_card_prices)
        [$commerceId, $creatorUserId] = $this->resolveCommerceContext($userId, $role);

        // 3. Moneda del país seleccionado
        $currencyId     = $this->currencies->getValueByCountry($countryId, 'id');
        $currencySymbol = $this->currencies->getValueByCountry($countryId, 'symbol');

        if (!$currencyId) {
            throw new DomainException('No se encontró moneda para el país seleccionado.');
        }

        // 4. Tasa de cambio: propia del commerce si existe, si no hereda la oficial
        //    (exchange_rates) — nunca asume 1:1 en silencio, ver ExchangeRateDomainService.
        $exchangeRate = $this->exchangeRateDomain->getExchangeRateByCurrency((int) $currencyId, (int) $userId);

        // 5. Precio personalizado en bingo_card_prices (room + currency + creator_user_id del commerce)
        $customPrice = BingoCardPrice::byRoom($roomId)
            ->byCurrency($currencyId)
            ->where('creator_user_id', $creatorUserId)
            ->value('card_price');

        $isCustomPrice = $customPrice !== null;

        if ($isCustomPrice) {
            $cardPrice = $this->priceCalculator->calculate($customPrice, true, $exchangeRate);
        } else {
            $room         = BingoRoom::select('id')->with('config:id,room_id,card_price_usd')->find($roomId);
            $roomCardPrice = $room?->config?->card_price_usd ?? 0;
            $cardPrice    = $this->priceCalculator->calculate($roomCardPrice, false, $exchangeRate);
        }

        // 6. Comisión de la entidad autenticada según su rol
        $commission = $this->resolveEntityCommission($userId, $role);

        return [
            'card_price'        => (float) $cardPrice,
            'currency_symbol'   => (string) ($currencySymbol ?? ''),
            'exchange_rate'     => $exchangeRate,
            'is_custom_price'   => $isCustomPrice,
            'seller_commission' => $commission,
        ];
    }

    /**
     * Resuelve el commerce_id y el creator_user_id (user_id del commerce) según el rol.
     *
     * - commerce: el propio commerce; creator_user_id = $userId.
     * - seller:   el commerce del manager; creator_user_id = manager_user_id del seller.
     *
     * @param string $userId ID del usuario autenticado.
     * @param string $role   Rol activo ('commerce' | 'seller').
     *
     * @return array{0: string, 1: string} [commerce_id, creator_user_id]
     *
     * @throws DomainException Si el commerce no existe.
     */
    private function resolveCommerceContext(string $userId, string $role): array
    {
        return match ($role) {
            'commerce' => [
                $this->commerceRepository->getValueByUserId($userId, 'id'),
                $userId,
            ],
            'seller' => (function () use ($userId): array {
                $managerUserId = $this->sellerRepository->getValueByUserId($userId, 'manager_user_id');
                $commerceId    = $this->commerceRepository->getValueByUserId($managerUserId, 'id');

                if (!$commerceId) {
                    throw new DomainException('Comercio asociado al vendedor no encontrado.');
                }

                return [$commerceId, $managerUserId];
            })(),
            default => throw new DomainException("Rol sin soporte de commerce: {$role}"),
        };
    }

    /**
     * Resuelve la comisión de la entidad autenticada según su rol.
     *
     * Cada entidad (commerce, seller) almacena su propia comisión en la columna
     * `commission` de su tabla correspondiente.
     *
     * @param string $userId ID del usuario autenticado.
     * @param string $role   Rol activo ('commerce' | 'seller').
     *
     * @return float Porcentaje de comisión (ej: 5.00 = 5%).
     *
     * @throws DomainException Si el rol no tiene soporte de comisión.
     */
    private function resolveEntityCommission(string $userId, string $role): float
    {
        $commission = match ($role) {
            'commerce'              => $this->commerceRepository->getValueByUserId($userId, 'commission'),
            'seller'                => $this->sellerRepository->getValueByUserId($userId, 'commission'),
            'superadmin', 'admin'   => 0,
            default                 => throw new DomainException("Rol sin soporte de comisión: {$role}"),
        };

        return (float) ($commission ?? 0);
    }

    /**
     * Genera cartones de bingo para un jugador, operado por un vendedor.
     *
     * @param BingoCardGenerateDTO $dto
     * @return array{type: string, message: string, playerLink: string}
     * @throws DomainException
     */
    public function generate(BingoCardGenerateDTO $dto): array
    {
        // 1. Obtener seller autenticado
        // manager_user_id = user_id del commerce que gestiona este seller
        $seller = Seller::select(['id', 'commission', 'manager_user_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$seller) {
            throw new DomainException('Vendedor no encontrado.');
        }

        $creatorUser = $seller->manager_user_id;
        $commerce    = Commerce::where('user_id', $creatorUser)->value('id');

        if (!$commerce) {
            throw new DomainException('Comercio asociado no encontrado.');
        }

        // 2. Validar sala — solo se permiten salas con código ACTIVE o IN_PROGRESS
        $room = BingoRoom::select('id', 'status_id', 'prize_pool', 'creator_user_id')
            ->whereHas('status', fn($q) => $q->whereIn('code', ['ACTIVE', 'IN_PROGRESS']))
            ->where('id', $dto->roomId)
            ->first();

        if (!$room) {
            throw new DomainException('Sala no encontrada o no disponible.');
        }

        // 3. Obtener moneda por país
        $currency = $this->currencies->getValueByCountry($dto->countryId, 'id');

        if (!$currency) {
            throw new DomainException('Moneda no disponible para el país seleccionado.');
        }

        // 4. Tasa de cambio: propia del commerce si existe, si no hereda la oficial
        //    (exchange_rates) — lanza ExchangeRateException (DomainException) si tampoco existe ahí.
        $exchangeRate = round(
            $this->exchangeRateDomain->getExchangeRateByCurrency((int) $currency, (int) $creatorUser),
            2,
        );

        // 5. Calcular precio del cartón
        //    Busca precio personalizado en bingo_card_prices para la sala, moneda y commerce.
        //    creator_user_id = user_id del commerce manager del seller.
        $customPrice  = BingoCardPrice::byRoom($room->id)
            ->byCurrency($currency)
            ->where('creator_user_id', $creatorUser)
            ->value('card_price');

        $roomCardPrice = $room->config?->card_price_usd ?? $room->config?->card_price ?? 0;

        $cardPrice = $customPrice !== null
            ? $this->priceCalculator->calculate($customPrice, true, $exchangeRate)
            : $this->priceCalculator->calculate($roomCardPrice, false, $exchangeRate);
        $cardPriceUsd = $this->toUsdAmount($cardPrice, $exchangeRate);

        // 6. Buscar jugador existente en la sala
        $existingRoomUser = ExternalPlayer::select('id', 'link')
            ->where('game_id', 1)
            ->where('room_id', $room->id)
            ->where('phone', $dto->playerPhone)
            ->value('room_user_id');

        $arrInitials = $this->initialCharacters([$dto->playerName, $dto->playerLastName]);


        // Definir ID del juego
        $gameId = Game::getValueByName('BINGO', 'id');

        $cardOrder = [
            'gameId'        => $gameId,
            'roomId'        => $room->id,
            'creator'       => ['id' => $creatorUser, 'type' => 'user_id'],
            'seller'        => $seller->id,
            'commerce'      => $commerce,
            'currency'      => $currency,
            'cardCount'     => $dto->cardCount,
            'cardPrice'     => $cardPrice,
            'cardPriceUsd'  => $cardPriceUsd,
            'saleAmountUsd' => round($cardPriceUsd * $dto->cardCount, 3),
            'country'       => $dto->countryId,
            'playerPhone'   => $dto->playerPhone,
            'playerName'    => $dto->playerName,
            'playerLastName' => $dto->playerLastName,
            'initials'      => $arrInitials[0] . $arrInitials[1],
            'exchangeRate'  => $exchangeRate,
            'receiptNumber' => $dto->receiptNumber,
            'receiptImage'  => $dto->receiptImage,
        ];

        if ($existingRoomUser) {
            $cardOrder['roomUser'] = $existingRoomUser;
        }

        // 7. Ejecutar transacción atómica
        $result = DB::transaction(function () use ($cardOrder) {
            $roomUser = $this->cardGenerationService->generateCards($cardOrder);

            if (!$roomUser) {
                throw new DomainException('Error al generar cartones');
            }

            $cardOrder['roomUser'] = $roomUser;

            $cardOrder = $this->handleExternalPlayers($cardOrder);

            $playerLink = $this->getGameLinkByPlayer($cardOrder['roomUser']['id'] ?? null);

            return [
                'type'       => 'success',
                'message'    => 'Cartones generados correctamente.',
                'playerLink' => $playerLink,
            ];
        });

        // 8. Emitir evento de actualización de sala via WebSocket (fuera de la transacción)
        //    Recarga los datos frescos después del commit para garantizar consistencia.
        $this->broadcastRoomUpdated((string) $room->id);

        return $result;
    }

    /**
     * Compra de cartones desde la vista game-rooms (commerce/seller para su player).
     *
     * Los datos del jugador (teléfono, nombre) vienen del contexto del commerce/seller
     * autenticado — no se requiere formulario de datos del jugador.
     *
     * @param \App\Domains\Games\Bingo\DTOs\Room\BingoRoomPlayerBuyDTO $dto
     * @return array{type: string, message: string, prize_pool?: float, card_count?: int}
     * @throws DomainException
     */
    public function buyFromGameRooms(\App\Domains\Games\Bingo\DTOs\Room\BingoRoomPlayerBuyDTO $dto): array
    {
        $userId = (string) $this->userContext->resolveUserId();
        $role   = $this->roleUserService->getActiveUserRolePrefix((int) $userId);

        // 1. Resolver commerce responsable según rol
        [$commerceId, $creatorUserId] = $this->resolveCommerceContext($userId, $role);

        // 2. Validar sala — permite ACTIVE o IN_PROGRESS
        $room = BingoRoom::select('id', 'uuid_code', 'status_id', 'prize_pool', 'creator_user_id')
            ->with(['status:id,code', 'config:id,room_id,card_price_usd'])
            ->whereHas('status', fn($q) => $q->whereIn('code', ['ACTIVE', 'IN_PROGRESS']))
            ->where('id', $dto->roomId)
            ->first();

        if (!$room) {
            throw new DomainException('Sala no encontrada o no disponible.');
        }

        // 3. Moneda del país seleccionado
        $currencyId = $this->currencies->getValueByCountry($dto->countryId, 'id');

        if (!$currencyId) {
            throw new DomainException('Moneda no disponible para el país seleccionado.');
        }

        // 4. Tasa de cambio: propia del commerce si existe, si no hereda la oficial
        //    (exchange_rates) — lanza ExchangeRateException (DomainException) si tampoco existe ahí.
        $exchangeRate = round(
            $this->exchangeRateDomain->getExchangeRateByCurrency((int) $currencyId, (int) $userId),
            2,
        );

        // 5. Precio del cartón
        $customPrice = BingoCardPrice::byRoom($room->id)
            ->byCurrency($currencyId)
            ->where('creator_user_id', $creatorUserId)
            ->value('card_price');

        $cardPrice = $customPrice !== null
            ? $this->priceCalculator->calculate($customPrice, true, $exchangeRate)
            : $this->priceCalculator->calculate($room->config?->card_price_usd ?? 0, false, $exchangeRate);
        $cardPriceUsd = $this->toUsdAmount($cardPrice, $exchangeRate);

        // 6. Seller ID (null si es commerce)
        $sellerId = $role === 'seller'
            ? $this->sellerRepository->getValueByUserId($userId, 'id')
            : null;

        $currencySymbol = $this->currencies->find($currencyId, ['symbol'])?->symbol ?? '$';

        $cardOrder = [
            'gameId'         => Game::getValueByName('BINGO', 'id'),
            'roomId'         => $room->id,
            'creator'        => ['id' => $creatorUserId, 'type' => 'user_id'],
            'seller'         => $sellerId,
            'commerce'       => $commerceId,
            'currency'       => $currencyId,
            'cardCount'      => $dto->cardCount,
            'cardPrice'      => $cardPrice,
            'cardPriceUsd'   => $cardPriceUsd,
            'saleAmountUsd'  => round($cardPriceUsd * $dto->cardCount, 3),
            'country'        => $dto->countryId,
            'playerPhone'    => '',
            'playerName'     => '',
            'playerLastName' => '',
            'initials'       => '',
            'exchangeRate'   => $exchangeRate,
        ];

        $result = DB::transaction(function () use ($cardOrder) {
            $roomUser = $this->cardGenerationService->generateCards($cardOrder);

            if (!$roomUser) {
                throw new DomainException('Error al generar cartones');
            }

            $this->handleExternalPlayers($cardOrder);

            return [
                'type'       => 'success',
                'message'    => 'Cartones generados correctamente.',
                'card_count' => $cardOrder['cardCount'],
            ];
        });

        // Emitir evento de actualización de sala via WebSocket (fuera de la transacción)
        $fresh = $this->broadcastRoomUpdated((string) $room->id);

        // Notificar al usuario propietario de los cartones (canal privado) su nuevo total en esta sala,
        // junto con el precio y símbolo actualizados para que el frontend no requiera recarga.
        $this->broadcastPlayerCardsUpdated((string) $room->id, $creatorUserId, (float) $cardPrice, (string) $currencySymbol);

        // Invalidar el cache de viewGame — el usuario puede haber comprado su primer cartón
        // y el cache de la policy habría devuelto false durante hasta 30s tras la compra.
        \Illuminate\Support\Facades\Cache::forget("bingo:policy:viewGame:{$userId}:{$room->id}");

        // Notificar la venta a dashboards de admin/superadmin y al vendedor/comercio propietario.
        broadcast(new BingoCardSold(
            roomId: (string) $room->id,
            roomName: (string) $room->uuid_code,
            quantity: (int) $cardOrder['cardCount'],
            amountUsd: (float) $cardOrder['saleAmountUsd'],
            creatorUserId: (string) $creatorUserId,
        ));

        return array_merge($result, [
            'prize_pool' => $fresh?->prize_pool ?? $room->prize_pool,
            'currency' => $currencySymbol,
        ]);
    }

    /**
     * Recarga la sala con sus métricas frescas tras una compra de cartones y emite el
     * broadcast `BingoRoomUpdated` (canal público de la sala). Devuelve la sala fresca
     * para que el caller pueda usar su prize_pool actualizado en la respuesta HTTP.
     *
     * Se llama siempre fuera de la transacción de compra, para garantizar que el commit
     * ya ocurrió y los datos emitidos por WebSocket coinciden con lo persistido.
     */
    private function broadcastRoomUpdated(string $roomId, bool $toOthers = false): ?BingoRoom
    {
        $fresh = BingoRoom::select('id', 'prize_pool', 'total_players', 'total_cards')
            ->with('config:id,room_id,card_price_usd')
            ->find($roomId);

        if (!$fresh) {
            Log::warning('BingoRoomUpdated: No se encontró la sala fresca tras la compra', ['room_id' => $roomId]);
            return null;
        }

        // hashed_id es el ID que el frontend usa en rooms[].id (viene de fetchRoom con hashed_id).
        // El canal y el payload deben usar el mismo valor para que patchRoom() encuentre la sala.
        $event = new \App\Domains\Games\Bingo\Events\BingoRoomUpdated(
            roomId:       $fresh->hashed_id,
            prizePool:    (float)  $fresh->prize_pool,
            totalPlayers: (int)    $fresh->total_players,
            cardPriceUsd: (float) ($fresh->config?->card_price_usd ?? 0),
            totalCards:   (int)    $fresh->total_cards,
        );

        $toOthers ? broadcast($event)->toOthers() : broadcast($event);

        return $fresh;
    }

    /**
     * Notifica al comprador (canal privado `bingo.player.{userId}`) su nuevo total de
     * cartones en la sala, junto con el precio/moneda vigentes, para que el frontend
     * actualice sin recargar la página.
     */
    private function broadcastPlayerCardsUpdated(string $roomId, int|string $userId, float $cardPrice, string $currencySymbol): void
    {
        $totalCards = \App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->value('cards_purchased');

        if ($totalCards === null) {
            return;
        }

        // El frontend usa hashed_id en rooms[].id — el payload debe usar el mismo valor
        // para que la búsqueda findIndex(r => r.id === payload.room_id) encuentre la sala.
        $room = \App\Domains\Games\Bingo\Models\Room\BingoRoom::select('id')->find($roomId);
        $hashedRoomId = $room?->hashed_id ?? $roomId;

        broadcast(new \App\Domains\Games\Bingo\Events\BingoPlayerRoomCardsUpdated(
            userId:         (string) $userId,
            roomId:         $hashedRoomId,
            cardCount:      (int) $totalCards,
            cardPrice:      $cardPrice,
            currencySymbol: $currencySymbol,
        ));
    }

    /**
     * Resuelve el creator_user_id/manager_user_id según rol activo.
     */
    private function resolveCreatorOrManagerUserId(): int
    {
        $userId = $this->userContext->resolveUserId();
        $role   = $this->roleUserService->getActiveUserRolePrefix($userId);

        return match ($role) {
            'commerce', 'customer' => $userId,
            'seller' => $this->sellerRepository
                ->getValueByUserId($userId, 'manager_user_id'),
            default => throw new DomainException('Rol inválido'),
        };
    }

    /**
     * Obtener el precio del cartón para un jugador usando su billetera configurada.
     *
     * Para jugadores, usa la moneda de su billetera activa y obtiene
     * el precio según la lógica existente de precios personalizados y tasas de cambio.
     *
     * @param string $roomId ID de la sala
     * @param int    $userId ID del usuario
     * @return array
     */
    public function getPlayerCardPrice(string $roomId, int $userId): array
    {
        // Eager-load config en la misma query — evita la segunda query lazy al acceder a card_price_usd.
        $room = BingoRoom::with('config:id,room_id,card_price_usd,game_variant,allow_custom_card,custom_card_price_pct,custom_card_mode,custom_card_every_x,move_free_space_mode,move_free_space_price_pct')
            ->find($roomId);

        if (!$room) {
            throw new DomainException("Sala no encontrada con ID: {$roomId}");
        }

        // Obtener la billetera activa del jugador
        $activeWallets = $this->walletRepository->getActiveWalletsByUser((string)$userId, ['currency_id']);

        if ($activeWallets->isEmpty()) {
            throw new DomainException('El jugador no tiene billeteras activas configuradas');
        }

        // Usar la primera billetera activa (podría haber lógica para seleccionar una específica)
        $playerWallet = $activeWallets->first();
        $currencyId = $playerWallet->currency_id;

        // Obtener información de la moneda
        $currency = $this->currencies->find($currencyId, ['symbol']);
        $currencySymbol = $currency?->symbol ?? 'USD';

        // Obtener tasa de cambio para la moneda del jugador
        $exchangeRate = $this->currencyExchangeRate->getActiveRate($currencyId);

        // Buscar precio personalizado en bingo_card_prices (room + currency)
        // Para jugadores, no filtramos por creator_user_id para permitir precios globales
        $customPrice = BingoCardPrice::byRoom($roomId)
            ->byCurrency($currencyId)
            ->value('card_price');

        $isCustomPrice = $customPrice !== null;

        if ($isCustomPrice) {
            $cardPrice = $this->priceCalculator->calculate($customPrice, true, $exchangeRate);
        } else {
            // Usar precio base de la sala
            $roomCardPrice = $room?->config?->card_price_usd ?? 0.0;
            $cardPrice = $this->priceCalculator->calculate($roomCardPrice, false, $exchangeRate);
        }
        $cardPriceUsd = $this->toUsdAmount($cardPrice, $exchangeRate);

        $customCardMode = $room->config?->custom_card_mode;
        $customCardPricePct = match ($customCardMode) {
            'by_range' => $this->resolveSmartCustomCardPct($cardPriceUsd),
            default => $room->config?->custom_card_price_pct !== null ? (float) $room->config->custom_card_price_pct : null,
        };

        $isBingo75 = $room->config?->game_variant === BingoVariant::BINGO_75;
        $moveFreeSpaceMode = $isBingo75 ? $room->config?->move_free_space_mode : null;
        $moveFreeSpacePricePct = match ($moveFreeSpaceMode) {
            'by_range' => $this->resolveSmartCustomCardPct($cardPriceUsd),
            'paid' => $room->config?->move_free_space_price_pct !== null ? (float) $room->config->move_free_space_price_pct : null,
            default => null,
        };

        return [
            'card_price' => (float) $cardPrice,
            'card_price_usd' => (float) $cardPriceUsd,
            'currency_symbol' => (string) ($currencySymbol ?? 'USD'),
            'exchange_rate' => $exchangeRate,
            'is_custom_price' => $isCustomPrice,
            'custom_card_mode' => $customCardMode,
            'custom_card_price_pct' => $customCardPricePct,
            'custom_card_every_x' => $room->config?->custom_card_every_x,
            'move_free_space_mode' => $moveFreeSpaceMode,
            'move_free_space_price_pct' => $moveFreeSpacePricePct,
        ];
    }

    /**
     * Igual que getPlayerCardPrice() pero para varias salas a la vez.
     *
     * La billetera/moneda del jugador es la misma para todas las salas, así
     * que se resuelve una sola vez; salas y precios personalizados se traen
     * en dos queries con whereIn en lugar de N queries por sala (usado por
     * el lobby, que resuelve el precio de cada sala activa en un mismo request).
     *
     * @param string[] $roomIds
     * @return array<string, array> Precio por sala, indexado por room_id. Las
     *   salas sin billetera/tasa resolvible simplemente no aparecen en el resultado.
     */
    public function getPlayerCardPricesForRooms(array $roomIds, int $userId): array
    {
        if (empty($roomIds)) {
            return [];
        }

        $activeWallets = $this->walletRepository->getActiveWalletsByUser((string) $userId, ['currency_id']);

        if ($activeWallets->isEmpty()) {
            return [];
        }

        $playerWallet = $activeWallets->first();
        $currencyId = $playerWallet->currency_id;

        $currency = $this->currencies->find($currencyId, ['symbol']);
        $currencySymbol = $currency?->symbol ?? 'USD';

        $exchangeRate = $this->currencyExchangeRate->getActiveRate($currencyId);

        $rooms = BingoRoom::with('config:id,room_id,card_price_usd,game_variant')
            ->whereIn('id', $roomIds)
            ->get()
            ->keyBy('id');

        $customPrices = BingoCardPrice::whereIn('room_id', $roomIds)
            ->byCurrency($currencyId)
            ->pluck('card_price', 'room_id');

        $results = [];

        foreach ($roomIds as $roomId) {
            $room = $rooms->get($roomId);

            if (!$room) {
                continue;
            }

            $customPrice = $customPrices->get($roomId);
            $isCustomPrice = $customPrice !== null;

            if ($isCustomPrice) {
                $cardPrice = $this->priceCalculator->calculate($customPrice, true, $exchangeRate);
            } else {
                $roomCardPrice = $room->config?->card_price_usd ?? 0.0;
                $cardPrice = $this->priceCalculator->calculate($roomCardPrice, false, $exchangeRate);
            }

            $results[$roomId] = [
                'card_price' => (float) $cardPrice,
                'card_price_usd' => (float) $this->toUsdAmount($cardPrice, $exchangeRate),
                'currency_symbol' => (string) ($currencySymbol ?? 'USD'),
                'exchange_rate' => $exchangeRate,
                'is_custom_price' => $isCustomPrice,
            ];
        }

        return $results;
    }

    /**
     * Máximo de cartones personalizables permitidos en una compra de `$cardCount`
     * cartones cuando la sala usa custom_card_mode = 'every_x': por cada ciclo
     * de (X normales + 1 personalizable) solo el último cartón del ciclo puede
     * personalizarse. Ej. X=3 → de cada 4 cartones consecutivos, 1 personalizable
     * (posiciones 4, 8, 12...). La regla es por proceso de compra — no se basa
     * en el histórico acumulado del jugador en la sala.
     */
    private function maxCustomCardsAllowed(int $everyX, int $cardCount): int
    {
        if ($everyX < 1) {
            return $cardCount;
        }

        return intdiv($cardCount, $everyX + 1);
    }

    /**
     * Recargo máximo permitido para cartón personalizado: nunca más del 5% del
     * precio base del cartón, sin importar la modalidad ni la moneda.
     */
    private const MAX_CUSTOM_CARD_SURCHARGE_PCT = 5.0;

    /**
     * Calcula el monto total a cobrar por los `$customCount` cartones personalizados
     * de esta compra, según la modalidad configurada en la sala (custom_card_mode):
     *
     * - 'paid' (o sin modo, retrocompatibilidad): recargo custom_card_price_pct (%)
     *   sobre el precio base, aplicado a todos los cartones personalizados.
     * - 'by_range': recargo % calculado automáticamente según el precio base del
     *   cartón en USD (ver resolveSmartCustomCardPct), sin que el admin configure
     *   un valor manual.
     * - 'free' / 'every_x': sin recargo — precio base por cartón. En 'every_x' el
     *   límite de cuántos cartones pueden personalizarse ya se validó antes de
     *   llegar aquí (ver maxCustomCardsAllowed).
     *
     * Los `$movedFreeSpaceCount` cartones (subconjunto de los personalizados que
     * además movieron la casilla libre) pagan, adicionalmente, el recargo de
     * move_free_space_mode — independiente y sumado al recargo de personalización.
     *
     * En todos los casos con recargo, cada % efectivo se acota individualmente a
     * MAX_CUSTOM_CARD_SURCHARGE_PCT como salvaguarda dura, independiente de lo
     * que tenga guardado la configuración de la sala.
     */
    private function resolveCustomCardsTotal(
        BingoRoom $room,
        float $basePrice,
        int $customCount,
        int $movedFreeSpaceCount = 0,
        ?float $cardPriceUsd = null,
    ): float {
        $mode = $room->config?->custom_card_mode;

        $customPct = match (true) {
            $mode === 'free' || $mode === 'every_x' => 0.0,
            $mode === 'by_range' => $this->resolveSmartCustomCardPct($cardPriceUsd ?? $basePrice),
            default => (float) ($room->config?->custom_card_price_pct ?? 0),
        };
        $customPct = min($customPct, self::MAX_CUSTOM_CARD_SURCHARGE_PCT);

        $moveFreeSpaceMode = $room->config?->move_free_space_mode;
        $moveFreeSpacePct = match ($moveFreeSpaceMode) {
            'by_range' => $this->resolveSmartCustomCardPct($cardPriceUsd ?? $basePrice),
            'paid' => (float) ($room->config?->move_free_space_price_pct ?? 0),
            default => 0.0, // 'free' o null: sin recargo adicional
        };
        $moveFreeSpacePct = min($moveFreeSpacePct, self::MAX_CUSTOM_CARD_SURCHARGE_PCT);

        $regularCustomCount = $customCount - $movedFreeSpaceCount;

        $regularCustomPrice = $customPct > 0 ? round($basePrice * (1 + $customPct / 100), 4) : $basePrice;
        $movedFreeSpacePrice = round($basePrice * (1 + ($customPct + $moveFreeSpacePct) / 100), 4);

        return round($regularCustomPrice * $regularCustomCount + $movedFreeSpacePrice * $movedFreeSpaceCount, 4);
    }

    /**
     * Cuenta cuántas de las `$matrices` (cartones personalizados) tienen su única
     * casilla libre en una posición distinta a la fija de la variante — es decir,
     * cuántas efectivamente movieron la libre (mover es opcional, aunque la sala
     * lo permita, el jugador puede dejarla en su sitio de siempre).
     *
     * @param array<int, array<string, array<int, int|null>>> $matrices
     */
    private function countMovedFreeSpaceCards(string $variant, array $matrices): int
    {
        $columns = BingoVariant::columns($variant);
        $count = 0;

        foreach ($matrices as $matrix) {
            foreach ($columns as $col) {
                $freeRowInMatrix = array_search(null, $matrix[$col['label']] ?? [], true);
                if ($freeRowInMatrix === false) {
                    continue;
                }
                if ($col['freeRow'] !== $freeRowInMatrix) {
                    $count++;
                }
                break; // ya se encontró el único null del cartón
            }
        }

        return $count;
    }

    /**
     * Modalidad 'by_range': determina el % de recargo automáticamente según el
     * precio base del cartón en USD (referencia estable entre monedas). A menor
     * precio del cartón, mayor es el % relativo permitido (dentro del techo de
     * 5%) porque un monto fijo pequeño representaría un porcentaje irrisorio;
     * a mayor precio, el % baja para no encarecer demasiado el cartón.
     */
    private function resolveSmartCustomCardPct(float $cardPriceUsd): float
    {
        return match (true) {
            $cardPriceUsd <= 10.0 => 2.0,
            $cardPriceUsd <= 50.0 => 1.5,
            default => 1.0,
        };
    }

    /**
     * Compra de cartones para jugadores (flujo simplificado).
     *
     * @param string     $roomId         ID de la sala
     * @param int        $cardCount      Número de cartones
     * @param array|null $customMatrices Array de matrices, una por cartón. Si se envía,
     *   count($customMatrices) debe ser === $cardCount. Cada elemento es el mismo shape
     *   que BingoVariant::generateCardMatrix. Se valida cada matriz individualmente.
     * @return array
     */
    public function playerBuyFromGameRooms(string $roomId, int $cardCount, ?array $customMatrices = null): array
    {
        $userId = $this->userContext->resolveUserId();

        if (!$this->userContext->userHasRole('player')) {
            throw new DomainException('Acceso no autorizado para esta operación');
        }

        $room = BingoRoom::with('config')->findOrFail($roomId);
        $variant = $room->config?->game_variant ?? BingoVariant::BINGO_75;

        $customCount = $customMatrices !== null ? count($customMatrices) : 0;
        $movedFreeSpaceCount = 0;

        if ($customCount > 0) {
            if (!$room->config?->allow_custom_card) {
                throw new DomainException('Esta sala no permite cartones personalizados.');
            }

            if ($customCount > $cardCount) {
                throw new DomainException('El número de matrices personalizadas no puede superar card_count.');
            }

            if ($room->config->custom_card_mode === 'every_x') {
                $everyX = (int) ($room->config->custom_card_every_x ?? 0);
                $maxAllowed = $this->maxCustomCardsAllowed($everyX, $cardCount);
                if ($customCount > $maxAllowed) {
                    throw new DomainException(
                        "Con esta cantidad de cartones solo puedes personalizar {$maxAllowed}."
                    );
                }
            }

            $allowMoveFreeSpace = $room->config?->move_free_space_mode !== null
                && $variant === BingoVariant::BINGO_75;

            foreach ($customMatrices as $i => $matrix) {
                $error = BingoVariant::validateCustomMatrix($variant, $matrix, $allowMoveFreeSpace);
                if ($error !== null) {
                    throw new DomainException("Cartón #" . ($i + 1) . ": {$error}");
                }
            }

            // Cuántos de los cartones personalizados efectivamente movieron la libre
            // (su único null está fuera de la posición fija de la variante). Solo estos
            // pagan el recargo de move_free_space_mode — mover la libre es opcional
            // incluso cuando la sala lo permite.
            if ($allowMoveFreeSpace) {
                $movedFreeSpaceCount = $this->countMovedFreeSpaceCards($variant, $customMatrices);
            }
        }

        // Obtener precio para jugador
        $priceInfo  = $this->getPlayerCardPrice($roomId, $userId);
        $basePrice  = $priceInfo['card_price'];

        // Precio de cartones personalizados según la modalidad configurada en la sala
        // (custom_card_mode). Solo aplica a cartones con matrices enviadas (los sin
        // matriz se generan al azar → siempre precio base).
        $regularCount = $cardCount - $customCount;

        $customTotal = $customCount > 0
            ? $this->resolveCustomCardsTotal($room, $basePrice, $customCount, $movedFreeSpaceCount, $priceInfo['card_price_usd'])
            : 0.0;

        $totalAmount = round($basePrice * $regularCount + $customTotal, 4);

        // Obtener billetera activa del jugador con saldo
        $activeWallets = $this->walletRepository->getActiveWalletsByUser((string)$userId, ['id', 'currency_id', 'balance', 'bonus_balance']);

        if ($activeWallets->isEmpty()) {
            throw new DomainException('El jugador no tiene billeteras activas configuradas');
        }

        $playerWallet = $activeWallets->first();
        $currencyId = $playerWallet->currency_id;

        // Verificación optimista (fail-fast antes de generar cartones); la
        // verificación autoritativa ocurre dentro de recordCardPurchase() bajo
        // lockForUpdate(). Se considera bono + saldo real, ya que la compra
        // consume primero el bono.
        if (! $playerWallet->hasSufficientPlayableBalance($totalAmount)) {
            $currentBalance = (float) $playerWallet->getPlayableBalance();
            throw new DomainException('Saldo insuficiente en la billetera. Saldo actual: ' . $currentBalance . ', Monto requerido: ' . $totalAmount);
        }

        // Preparar array para cartonStore. El descuento de billetera no ocurre en
        // generateCards() — lo hace recordCardPurchase() abajo, con lock sobre la wallet.
        $cardOrder = [
            'roomId'         => $roomId,
            'creator'        => ['id' => $userId, 'type' => 'player'],
            'seller'         => null,
            'seller_type'    => 'USER',
            'currency'       => $currencyId,
            'cardCount'      => $cardCount,
            'cardPrice'      => $basePrice,   // precio unitario base (el lote usa sale_amount para el total)
            'country'        => null,
            'playerPhone'    => '',
            'playerName'     => '',
            'playerLastName' => '',
            'initials'       => '',
            'cardPriceUsd'   => $priceInfo['card_price_usd'],
            'saleAmountUsd'  => round($totalAmount / max($priceInfo['exchange_rate'], 0.0001), 3),
            'exchangeRate'   => $priceInfo['exchange_rate'],
            'playerUser'     => null,
            'playerLink'     => true,
            'gameVariant'    => $room->config?->game_variant ?? BingoVariant::BINGO_75,
            'customCardNumbers' => $customMatrices,
        ];

        // Generar cartones y debitar wallet en una sola transacción atómica.
        // Si recordCardPurchase falla, generateInTransaction se revierte y el jugador
        // no queda con cartones sin pago.
        $description = "Compra de {$cardCount} cartón(es) — Sala {$room->uuid_code}";
        $newBalance = null;
        DB::transaction(function () use ($cardOrder, $playerWallet, $totalAmount, $description, $roomId, $userId, &$newBalance) {
            $this->cardGenerationService->generateInTransaction($cardOrder);
            $purchase = $this->walletService->recordCardPurchase($playerWallet->id, $totalAmount, $description);
            $newBalance = (float) $purchase['wallet']->getPlayableBalance();

            // Propagar el desglose bono/real al lote recién creado para poder
            // reembolsar proporcionalmente si la sala se cancela (ver
            // WalletService::recordRefund() y BingoCardLotRefundJob). Solo un
            // lote se crea por llamada (createCardLot() en generateCards()),
            // así que el más reciente para esta sala+usuario dentro de esta
            // misma transacción es inequívocamente el que se acaba de generar.
            if ($purchase['bonus_spent'] > 0) {
                \App\Domains\Games\Bingo\Models\Card\BingoCardLot::where('room_id', $roomId)
                    ->where('user_id', $userId)
                    ->latest('created_at')
                    ->first()
                    ?->update(['bonus_amount' => $purchase['bonus_spent']]);
            }
        });

        // Emitir evento de actualización de sala via WebSocket (fuera de la transacción)
        $fresh = $this->broadcastRoomUpdated($roomId, toOthers: true);

        // Notificar al jugador (canal privado) su nuevo total de cartones en esta sala,
        // junto con el precio y símbolo actualizados para que el frontend no requiera recarga.
        $this->broadcastPlayerCardsUpdated($roomId, $userId, (float) $priceInfo['card_price'], (string) $priceInfo['currency_symbol']);

        // Notificar la venta a dashboards de admin/superadmin (compra directa del jugador, sin vendedor).
        broadcast(new BingoCardSold(
            roomId: (string) $room->id,
            roomName: (string) $room->uuid_code,
            quantity: $cardCount,
            amountUsd: (float) $cardOrder['saleAmountUsd'],
        ));

        return [
            'message'        => 'Cartones generados exitosamente',
            'type'           => 'success',
            'card_count'     => $cardCount,
            'prize_pool'     => $fresh?->prize_pool ?? $room->prize_pool,
            'currency'       => $priceInfo['currency_symbol'],
            'exchange_rate'  => $priceInfo['exchange_rate'],
            'wallet_balance' => $newBalance,
        ];
    }

    /**
     * Respuesta vacía estándar.
     */
    private function emptyResponse(): array
    {
        return [
            'exists' => false,
            'rooms'  => []
        ];
    }

    private function toUsdAmount(float $amount, float $exchangeRate): float
    {
        if ($exchangeRate <= 0) {
            throw new DomainException('Tasa de cambio inválida para convertir el monto a USD.');
        }

        return round($amount / $exchangeRate, 3);
    }
}

