<?php

namespace App\Services\Games\Bingo\Card\Generate;

use App\DTOs\Games\Bingo\Card\Generate\{BingoCardGenerateContexDTO, BingoCardGenerateDTO};
use App\Events\Wallet\WalletUpdated;
use App\Domains\Games\Bingo\Models\Card\CardPrice\BingoCardPrice;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Services\Games\Bingo\Card\BingoCardPriceCalculator;
use App\Services\Games\Bingo\Card\BingoCardGenerationService;
use App\Services\NotificationService;
use App\Services\WalletService;
use App\Traits\Users\PlayerLinkTrait;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Contracts\Games\Bingo\Card\CardPrice\BingoCardPriceRepositoryInterface;
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
        private readonly \App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface $exchangeRateRepository,
        private readonly \App\Contracts\Roles\Customer\CustomerRepositoryInterface $customerRepository,
        private readonly \App\Contracts\Roles\Seller\SellerRepositoryInterface $sellerRepository,
        private readonly \App\Contracts\Roles\Seller\SellerCurrencyRepositoryInterface $sellerCurrencyRepository,
        private readonly BingoCardPriceRepositoryInterface $cardPriceRepository,
        private readonly \App\Contracts\Games\Common\GameMode\GameModeRepositoryInterface $gameModes,
        private readonly \App\Contracts\Games\Common\Room\RoomTypeRepositoryInterface $roomTypes,
        private readonly \App\Contracts\Games\Common\PrizePool\PrizePoolTypeRepositoryInterface $prizePoolTypes,
        private readonly BingoCardPriceCalculator $priceCalculator,
        private readonly \App\Contracts\WalletRepositoryInterface $walletRepository,
        private readonly WalletService $walletService,
        private readonly \App\Services\Games\Bingo\Card\BingoCardGenerationService $cardGenerationService,
        private readonly NotificationService $notificationService,
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

        // Monedas explícitamente permitidas en la sala (JSON nullable → fallback a [])
        $allowedCurrenciesRoom = BingoRoomConfig::where('room_id', $roomId)->value('allowed_currencies') ?? [];

        // Construir los conjuntos a intersectar (todos como strings para evitar
        // comparaciones fallidas por tipo int vs string en los IDs)
        $sets = [
            array_map('strval', $entityCurrencies),
            array_map('strval', $allowedCurrenciesRoom),
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
        $entityId = $this->roleEntityResolver->resolveEntityId($userId, $role);

        // 2. Commerce responsable y user_id de su creador (para filtrar bingo_card_prices)
        [$commerceId, $creatorUserId] = $this->resolveCommerceContext($userId, $role);

        // 3. Moneda del país seleccionado
        $currencyId     = $this->currencies->getValueByCountry($countryId, 'id');
        $currencySymbol = $this->currencies->getValueByCountry($countryId, 'symbol');

        if (!$currencyId) {
            throw new DomainException('No se encontró moneda para el país seleccionado.');
        }

        // 4. Tasa de cambio activa del commerce para esa moneda
        $exchangeRate = (float) $this->exchangeRateRepository->getCommerceCurrencyValue($commerceId, $currencyId);

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
            $roomCtnPrice = $room?->config?->card_price_usd ?? 0;
            $cardPrice    = $this->priceCalculator->calculate($roomCtnPrice, false, $exchangeRate);
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
            'commerce' => $this->commerceRepository->getValueByUserId($userId, 'commission'),
            'seller'   => $this->sellerRepository->getValueByUserId($userId, 'commission'),
            default    => throw new DomainException("Rol sin soporte de comisión: {$role}"),
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

        // 4. Calcular tasa de cambio
        $exchangeRateValue = $this->exchangeRateRepository->getCommerceCurrencyValue($commerce, $currency);

        if (!$exchangeRateValue || $exchangeRateValue <= 0) {
            throw new DomainException('No se encontró una tasa de cambio válida para el comercio y moneda seleccionados.');
        }

        $exchangeRate = round($exchangeRateValue, 2);

        // 5. Calcular precio del cartón
        //    Busca precio personalizado en bingo_card_prices para la sala, moneda y commerce.
        //    creator_user_id = user_id del commerce manager del seller.
        $customPrice  = BingoCardPrice::byRoom($room->id)
            ->byCurrency($currency)
            ->where('creator_user_id', $creatorUser)
            ->value('card_price');

        $roomCtnPrice = $room->config?->card_price_usd ?? $room->config?->card_price ?? 0;

        $cardPrice = $customPrice !== null
            ? $this->priceCalculator->calculate($customPrice, true, $exchangeRate)
            : $this->priceCalculator->calculate($roomCtnPrice, false, $exchangeRate);
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

        $arrCtn = [
            'gameId'        => $gameId,
            'roomId'        => $room->id,
            'creator'       => ['id' => $creatorUser, 'type' => 'user_id'],
            'seller'        => $seller->id,
            'commerce'      => $commerce,
            'currency'      => $currency,
            'numOfCtns'     => $dto->numCtns,
            'cardPrice'     => $cardPrice,
            'cardPriceUsd'  => $cardPriceUsd,
            'saleAmountUsd' => round($cardPriceUsd * $dto->numCtns, 3),
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
            $arrCtn['roomUser'] = $existingRoomUser;
        }

        // 7. Ejecutar transacción atómica
        $result = DB::transaction(function () use ($arrCtn, $room) {
            $roomUser = $this->cardGenerationService->generateCards($arrCtn);

            if (!$roomUser) {
                throw new DomainException('Error al generar cartones');
            }

            $arrCtn['roomUser'] = $roomUser;

            $arrCtn = $this->handleExternalPlayers($arrCtn);

            $playerLink = $this->getGameLinkByPlayer($arrCtn['roomUser']['id'] ?? null);

            return [
                'type'       => 'success',
                'message'    => 'Cartones generados correctamente.',
                'playerLink' => $playerLink,
            ];
        });

        // 8. Emitir evento de actualización de sala via WebSocket (fuera de la transacción)
        //    Recarga los datos frescos después del commit para garantizar consistencia.
        $fresh = BingoRoom::select('id', 'prize_pool', 'total_players')
            ->with('config:id,room_id,card_price_usd')
            ->find($room->id);

        if ($fresh) {
            broadcast(new \App\Events\Games\Bingo\BingoRoomUpdated(
                roomId: (string) $fresh->id,
                prizePool: (float)  $fresh->prize_pool,
                totalPlayers: (int)    $fresh->total_players,
                cardPriceUsd: (float) ($fresh->config?->card_price_usd ?? 0),
            ));
        }

        return $result;
    }

    /**
     * Compra de cartones desde la vista game-rooms (commerce/seller para su player).
     *
     * Los datos del jugador (teléfono, nombre) vienen del contexto del commerce/seller
     * autenticado — no se requiere formulario de datos del jugador.
     *
     * @param \App\DTOs\Games\Bingo\Room\BingoRoomPlayerBuyDTO $dto
     * @return array{type: string, message: string, prize_pool?: float, ctns?: int}
     * @throws DomainException
     */
    public function buyFromGameRooms(\App\DTOs\Games\Bingo\Room\BingoRoomPlayerBuyDTO $dto): array
    {
        $userId = (string) $this->userContext->resolveUserId();
        $role   = $this->roleUserService->getActiveUserRolePrefix((int) $userId);

        // 1. Resolver commerce responsable según rol
        [$commerceId, $creatorUserId] = $this->resolveCommerceContext($userId, $role);

        // 2. Validar sala — permite ACTIVE o IN_PROGRESS
        $room = BingoRoom::select('id', 'status_id', 'prize_pool', 'creator_user_id')
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

        // 4. Tasa de cambio
        $exchangeRate = round(
            (float) $this->exchangeRateRepository->getCommerceCurrencyValue($commerceId, $currencyId),
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

        $arrCtn = [
            'gameId'         => Game::getValueByName('BINGO', 'id'),
            'roomId'         => $room->id,
            'creator'        => ['id' => $creatorUserId, 'type' => 'user_id'],
            'seller'         => $sellerId,
            'commerce'       => $commerceId,
            'currency'       => $currencyId,
            'numOfCtns'      => $dto->numCtns,
            'cardPrice'      => $cardPrice,
            'cardPriceUsd'   => $cardPriceUsd,
            'saleAmountUsd'  => round($cardPriceUsd * $dto->numCtns, 3),
            'country'        => $dto->countryId,
            'playerPhone'    => '',
            'playerName'     => '',
            'playerLastName' => '',
            'initials'       => '',
            'exchangeRate'   => $exchangeRate,
        ];

        $result = DB::transaction(function () use ($arrCtn, $room) {
            $roomUser = $this->cardGenerationService->generateCards($arrCtn);

            if (!$roomUser) {
                throw new DomainException('Error al generar cartones');
            }

            $this->handleExternalPlayers($arrCtn);

            return [
                'type'       => 'success',
                'message'    => 'Cartones generados correctamente.',
                'ctns'       => $arrCtn['numOfCtns'],
            ];
        });

        // Emitir evento de actualización de sala via WebSocket (fuera de la transacción)
        $fresh = BingoRoom::select('id', 'prize_pool', 'total_players')
            ->with('config:id,room_id,card_price_usd')
            ->find($room->id);

        if ($fresh) {
            broadcast(new \App\Events\Games\Bingo\BingoRoomUpdated(
                roomId: (string) $fresh->uuid_code,
                prizePool: (float)  $fresh->prize_pool,
                totalPlayers: (int)    $fresh->total_players,
                cardPriceUsd: (float) ($fresh->config?->card_price_usd ?? 0),
            ));
        } else {
            \Log::warning('BingoRoomUpdated: No se encontró la sala fresca en buyFromGameRooms', ['room_id' => $room->id]);
        }

        return array_merge($result, [
            'prize_pool' => $fresh?->prize_pool ?? $room->prize_pool,
            'currency' => $currencySymbol,
        ]);
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

    private function getPlayerExchangeRate(int $currencyId): float
    {
        if ($currencyId === 1) {
            return 1.0;
        }

        return (float) ExchangeRate::where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderBy('created_at', 'desc')
            ->value('amount')
            ?: 1.0;
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
        try {
            $room = BingoRoom::findOrFail($roomId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
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
        $exchangeRate = $this->getPlayerExchangeRate($currencyId);

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
            $roomCtnPrice = $room?->config?->card_price_usd ?? 0.0;
            $cardPrice = $this->priceCalculator->calculate($roomCtnPrice, false, $exchangeRate);
        }
        $cardPriceUsd = $this->toUsdAmount($cardPrice, $exchangeRate);

        return [
            'card_price' => (float) $cardPrice,
            'card_price_usd' => (float) $cardPriceUsd,
            'currency_symbol' => (string) ($currencySymbol ?? 'USD'),
            'exchange_rate' => $exchangeRate,
            'is_custom_price' => $isCustomPrice,
        ];
    }

    /**
     * Compra de cartones para jugadores (flujo simplificado).
     *
     * @param string $roomId ID de la sala
     * @param int    $numCtns Número de cartones
     * @return array
     */
    public function playerBuyFromGameRooms(string $roomId, int $numCtns): array
    {
        $userId = $this->userContext->resolveUserId();

        // Validar que el usuario tenga rol 'player'
        if (!$this->userContext->userHasRole('player')) {
            throw new DomainException('Acceso no autorizado para esta operación');
        }

        $room = BingoRoom::findOrFail($roomId);

        // Obtener precio para jugador
        $priceInfo = $this->getPlayerCardPrice($roomId, $userId);
        $cardPrice = $priceInfo['card_price'];
        $totalAmount = $cardPrice * $numCtns;

        // Obtener billetera activa del jugador con saldo
        $activeWallets = $this->walletRepository->getActiveWalletsByUser((string)$userId, ['id', 'currency_id', 'balance']);

        if ($activeWallets->isEmpty()) {
            throw new DomainException('El jugador no tiene billeteras activas configuradas');
        }

        $playerWallet = $activeWallets->first();
        $currencyId = $playerWallet->currency_id;
        $currentBalance = (float) $playerWallet->balance;

        // Verificar que tenga saldo suficiente
        if ($currentBalance < $totalAmount) {
            throw new DomainException('Saldo insuficiente en la billetera. Saldo actual: ' . $currentBalance . ', Monto requerido: ' . $totalAmount);
        }

        // Preparar array para cartonStore
        // Usamos type='player' para que generateCards omita el descuento de wallet legacy
        // (Wallet::decrement por columna). El descuento real lo hace recordCardPurchase abajo.
        $arrCtn = [
            'roomId'         => $roomId,
            'creator'        => ['id' => $userId, 'type' => 'player'],
            'seller'         => null,
            'currency'       => $currencyId,
            'numOfCtns'      => $numCtns,
            'cardPrice'      => $cardPrice,
            'country'        => null,
            'playerPhone'    => '',
            'playerName'     => '',
            'playerLastName' => '',
            'initials'       => '',
            'cardPriceUsd'   => $priceInfo['card_price_usd'],
            'saleAmountUsd'  => round($priceInfo['card_price_usd'] * $numCtns, 3),
            'exchangeRate'   => $priceInfo['exchange_rate'],
            'playerUser'     => null, // null para que generateCards no intente Wallet::decrement
            'playerLink'     => true, // flag para saltar el bloque de descuento legacy
        ];

        // Generar cartones — el descuento de billetera lo hace recordCardPurchase abajo
        $this->cardGenerationService->generateInTransaction($arrCtn, $room, $totalAmount);

        // Descuento de billetera + movimiento — única fuente de verdad
        $description = "Compra de {$numCtns} cartón(es) — Sala {$room->uuid_code}";
        $this->walletService->recordCardPurchase($playerWallet->id, $totalAmount, $description);

        // Emitir evento de actualización de sala via WebSocket (fuera de la transacción)
        $fresh = BingoRoom::select('id', 'prize_pool', 'total_players')
            ->with('config:id,room_id,card_price_usd')
            ->find($room->id);

        if ($fresh) {
            broadcast(new \App\Events\Games\Bingo\BingoRoomUpdated(
                roomId:       (string) $fresh->id,
                prizePool:    (float)  $fresh->prize_pool,
                totalPlayers: (int)    $fresh->total_players,
                cardPriceUsd: (float) ($fresh->config?->card_price_usd ?? 0),
            ))->toOthers();
        }

        return [
            'message'        => 'Cartones generados exitosamente',
            'type'           => 'success',
            'ctns'           => $numCtns,
            'prize_pool'     => $fresh?->prize_pool ?? $room->prize_pool,
            'currency'       => $priceInfo['currency_symbol'],
            'exchange_rate'  => $priceInfo['exchange_rate'],
            'wallet_balance' => $currentBalance - $totalAmount,
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
            throw new DomainException('Tasa de cambio invÃ¡lida para convertir el monto a USD.');
        }

        return round($amount / $exchangeRate, 3);
    }
}
