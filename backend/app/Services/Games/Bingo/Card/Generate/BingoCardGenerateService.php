<?php

namespace App\Services\Games\Bingo\Card\Generate;

use App\DTOs\Games\Bingo\Card\Generate\{BingoCardGenerateContexDTO, BingoCardGenerateDTO};
use App\Models\Games\Bingo\Card\CardPrice\BingoCardPrice;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Currency;
use App\Models\ExternalPlayers;
use App\Models\Games\Bingo\Room\BingoRoomConfig;
use App\Services\Games\Bingo\Card\BingoCardPriceCalculator;
use App\Traits\Games\Bingo\BingoTrait;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use App\Contracts\Games\Bingo\Card\CardPrice\BingoCardPriceRepositoryInterface;

/**
 * Servicio para la generación de cartones de Bingo.
 *
 * Encapsula el flujo "seller" del legacy BingoController::generateCard()
 * adaptado a la arquitectura de servicios del proyecto.
 */
final class BingoCardGenerateService
{
    use BingoTrait;

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
     * Lógica (igual a la usada en generate()):
     * 1. Resuelve la moneda del país seleccionado.
     * 2. Obtiene el commerce del seller autenticado.
     * 3. Obtiene la tasa de cambio del commerce para esa moneda.
     * 4. Busca el precio personalizado en bingo_card_prices (room + currency + creator_user_id).
     * 5. Calcula el precio final con BingoCardPriceCalculator::calculate().
     *
     * @param string $roomId    ID (hasheado) de la sala de bingo.
     * @param string $userId    ID del usuario autenticado.
     * @param string $countryId ID del país seleccionado (Snowflake, se pasa como string para evitar truncado).
     *
     * @return array{
     *     card_price: float,
     *     currency_symbol: string,
     *     exchange_rate: float,
     *     is_custom_price: bool
     * }
     *
     * @throws DomainException Si la moneda, el commerce o la tasa no se encuentran.
     */
    public function getCardPrice(string $roomId, string $userId, string $countryId): array
    {
        // 1. Seller autenticado → commerce_id del manager
        $seller = Seller::select(['id', 'commission', 'manager_user_id'])
            ->where('user_id', $userId)
            ->first();

        if (!$seller) {
            throw new DomainException('Vendedor no encontrado.');
        }

        $commerce = Commerce::where('user_id', $seller->manager_user_id)->value('id');

        if (!$commerce) {
            throw new DomainException('Comercio asociado no encontrado.');
        }

        // 2. Moneda del país (id + symbol para mostrar en UI)
        $currencyId     = $this->currencies->getValueByCountry($countryId, 'id');
        $currencySymbol = $this->currencies->getValueByCountry($countryId, 'symbol');

        if (!$currencyId) {
            throw new DomainException('No se encontró moneda para el país seleccionado.');
        }

        // 3. Tasa de cambio activa del commerce para esa moneda
        $exchangeRate = (float) $this->exchangeRateRepository->getCommerceCurrencyValue($commerce, $currencyId);

        // 4. Precio personalizado en bingo_card_prices (room + currency + creator_user_id del commerce)
        //    creator_user_id corresponde al user_id del commerce que gestiona la sala.
        $customPrice = BingoCardPrice::byRoom($roomId)
            ->byCurrency($currencyId)
            ->where('creator_user_id', $seller->manager_user_id)
            ->value('card_price');

        // 5. Si no hay precio personalizado, usar el precio base de la sala en USD × tasa
        $isCustomPrice = $customPrice !== null;

        if ($isCustomPrice) {
            $cardPrice = $this->priceCalculator->calculate($customPrice, true, $exchangeRate);
        } else {
            $room         = BingoRoom::select('id')->with('config:id,room_id,card_price_usd')->find($roomId);
            $roomCtnPrice = $room?->config?->card_price_usd ?? 0;
            $cardPrice    = $this->priceCalculator->calculate($roomCtnPrice, false, $exchangeRate);
        }

        return [
            'card_price'        => (float) $cardPrice,
            'currency_symbol'   => (string) ($currencySymbol ?? ''),
            'exchange_rate'     => $exchangeRate,
            'is_custom_price'   => $isCustomPrice,
            'seller_commission' => (float) $seller->commission,
        ];
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

        // 2. Validar sala (decodificar hash → ID real para query directa)
        $realRoomId = $this->decodeRoomId($dto->roomId);
        $room = BingoRoom::select('id', 'status_id', 'prize_pool', 'creator_user_id')
            ->where('id', $realRoomId)
            ->first();

        if (!$room || $room->status_id > 2) {
            throw new DomainException('Sala no encontrada o no disponible.');
        }

        // 3. Obtener moneda por país
        $currency = Currency::getValueByCountry($dto->countryId, 'id');

        if (!$currency) {
            throw new DomainException('Moneda no disponible para el país seleccionado.');
        }

        // 4. Calcular tasa de cambio
        $exchangeRate = round(
            CommerceExchangeRate::getCommerceCurrencyValue($commerce, $currency),
            2
        );

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

        // 6. Buscar jugador existente en la sala
        // $existingRoomUser = ExternalPlayers::select('id', 'link')
        //     ->where('game', 1)
        //     ->where('room', $room->id)
        //     ->where('phone', $dto->playerPhone)
        //     ->value('room_user');

        $arrInitials = $this->initialCharacters([$dto->playerName, $dto->playerLastName]);

        $arrCtn = [
            'room'          => $room->id,
            'creator'       => $creator,
            'seller'        => $seller->id,
            'commerce'      => $commerce,
            'currency'      => $currency,
            'numOfCtns'     => $dto->numCtns,
            'cardPrice'     => $cardPrice,
            'country'       => $dto->countryId,
            'playerPhone'   => $dto->playerPhone,
            'playerName'    => $dto->playerName,
            'playerLastName' => $dto->playerLastName,
            'initials'      => $arrInitials[0] . $arrInitials[1],
            'exchangeRate'  => $exchangeRate,
        ];

        // if ($existingRoomUser) {
        //     $arrCtn['roomUser'] = $existingRoomUser;
        // }

        // 7. Ejecutar transacción atómica
        return DB::transaction(function () use ($arrCtn, $room) {
            $cartonStore = $this->cartonStore($arrCtn);

            if (isset($cartonStore['Error'])) {
                throw new DomainException($cartonStore['Error']);
            }

            if (!isset($arrCtn['roomUser'])) {
                $arrCtn['roomUser'] = $cartonStore;
            }

            $arrCtn = $this->handleExternalPlayers($arrCtn);

            $playerLink = $this->getGameLinkByPlayer($arrCtn['playerLink']);

            return [
                'type'       => 'success',
                'message'    => 'Cartones generados correctamente.',
                'playerLink' => $playerLink,
            ];
        });
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
     * Decodifica un room ID hasheado (Hashids) al ID real de la sala.
     *
     * El frontend recibe y envía el ID hasheado (ej: "ryzowvAxJrVNZ").
     * Las queries directas con `where('id', ...)` requieren el ID numérico real.
     *
     * @param string $hashedId ID hasheado recibido del frontend.
     * @return string ID real de la sala.
     * @throws DomainException Si el hash es inválido o no decodifica.
     */
    private function decodeRoomId(string $hashedId): string
    {
        $decoded = Hashids::connection('main')->decode($hashedId);

        if (empty($decoded)) {
            throw new DomainException('ID de sala inválido.');
        }

        return (string) $decoded[0];
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
}
