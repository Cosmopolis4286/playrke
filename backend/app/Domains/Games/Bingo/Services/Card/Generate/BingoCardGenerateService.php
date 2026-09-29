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
use App\Domains\Wallet\Services\WalletService;
use App\Services\Roles\Player\External\ExternalPlayerLinkService;
use App\Domains\Games\Bingo\Exceptions\Card\BingoCardGenerateException;
use App\Domains\Games\Bingo\Contracts\CommissionCalculationInterface;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Game;

/**
 * Servicio para la generación de cartones de Bingo.
 *
 */
final class BingoCardGenerateService
{
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
        private readonly \App\Contracts\Games\Common\ConnectionType\ConnectionTypeRepositoryInterface $connectionTypes,
        private readonly \App\Contracts\Games\Common\Room\RoomTypeRepositoryInterface $roomTypes,
        private readonly \App\Contracts\Games\Common\PrizePool\PrizePoolTypeRepositoryInterface $prizePoolTypes,
        private readonly BingoCardPriceCalculator $priceCalculator,
        private readonly \App\Domains\Wallet\Contracts\WalletRepositoryInterface $walletRepository,
        private readonly WalletService $walletService,
        private readonly \App\Domains\Games\Bingo\Services\Card\BingoCardGenerationService $cardGenerationService,
        private readonly \App\Services\ExchangeRate\CurrencyExchangeRateService $currencyExchangeRate,
        private readonly \App\Services\ExchangeRate\ExchangeRateDomainService $exchangeRateDomain,
        private readonly \App\Domains\Games\Bingo\Services\Room\BingoRoomCrudService $bingoRoomCrud,
        private readonly ExternalPlayerLinkService $externalPlayerLink,
        private readonly \App\Contracts\Roles\Subsidiary\SubsidiaryRepositoryInterface $subsidiaryRepository,
        private readonly \App\Services\NotificationService $notificationService,
        private readonly CommissionCalculationInterface $commissionCalculation,
    ) {}

    /**
     * Resolver contexto la vista de generar cartones de bingo.
     *
     * @param int $userId ID del usuario
     * @return BingoCardGenerateContexDTO
     */
    public function viewContext(int $userId): BingoCardGenerateContexDTO
    {
        [$canSell, $blockedMessage] = $this->resolveSellAvailability($userId);

        return new BingoCardGenerateContexDTO(
            gameModes: $this->connectionTypes->getActiveConnectionTypes(),
            roomTypes: $this->roomTypes->getActiveTypes(),
            prizePoolTypes: $this->prizePoolTypes->getActiveTypes(),
            canSell: $canSell,
            blockedMessage: $blockedMessage,
        );
    }

    /**
     * Determina si el usuario puede vender cartones desde esta vista.
     *
     * Equivale al `$status == 1` de la vista Blade legacy: se evalúa al cargar
     * la página para mostrar el aviso de inmediato, en lugar de que el vendedor
     * llene el formulario y solo reciba el 409 (hierarchyNotActive) al consultar
     * el precio o al generar. Mismo criterio que generate(): Seller ACTIVE +
     * agencia/comercio padre ACTIVE (Seller::hasActiveHierarchy()).
     *
     * @return array{0: bool, 1: string|null} [canSell, blockedMessage]
     */
    private function resolveSellAvailability(int $userId): array
    {
        if ($this->roleUserService->getActiveUserRolePrefix($userId) !== 'seller') {
            // BingoCardGenerateRequest::authorize() solo admite 'seller'.
            return [false, 'Solo los vendedores pueden generar cartones.'];
        }

        $seller = Seller::where('user_id', $userId)->first(['id', 'manager_user_id', 'subsidiary_id', 'status']);

        if (!$seller || !$seller->hasActiveHierarchy()) {
            return [false, '¡No puedes vender cartones, contacta a tu gestor!'];
        }

        return [true, null];
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
                'config:id,room_id,room_type_id,connection_type_id,card_price_usd,date,time'
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
     * Todas las salas vendibles de una fecha en una sola llamada, con los IDs
     * de tipo de sala / conexión / bote para que el punto de venta filtre en
     * el cliente (chips) sin la cascada de requests de fetchRoom().
     *
     * Mismas reglas de visibilidad que fetchRoom(): ACTIVE, de la fecha, y
     * propias del comercio o PUBLIC.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listSellableRooms(string $date): array
    {
        $creatorUserId = $this->resolveCreatorOrManagerUserId();

        return BingoRoom::query()
            ->active()
            ->forDate($date)
            ->accessibleBy($creatorUserId)
            ->join('bingo_rooms_config', 'bingo_rooms_config.room_id', '=', 'bingo_rooms.id')
            ->orderBy('bingo_rooms_config.time')
            ->get([
                'bingo_rooms.id',
                'bingo_rooms.uuid_code',
                'bingo_rooms.prize_pool',
                'bingo_rooms.total_players',
                'bingo_rooms.total_cards',
                'bingo_rooms.creator_user_id',
                'bingo_rooms_config.date',
                'bingo_rooms_config.time',
                'bingo_rooms_config.card_price_usd',
                'bingo_rooms_config.max_players',
                'bingo_rooms_config.room_type_id',
                'bingo_rooms_config.connection_type_id',
                'bingo_rooms_config.prize_pool_type_id',
            ])
            ->map(fn (BingoRoom $room) => [
                'id'                 => $room->hashed_id,
                'uuid_code'          => $room->uuid_code,
                'prize_pool'         => (float) $room->prize_pool,
                'total_players'      => (int) $room->total_players,
                'total_cards'        => (int) $room->total_cards,
                'is_own'             => (string) $room->creator_user_id === (string) $creatorUserId,
                'date'               => $room->getAttribute('date'),
                'time'               => $room->getAttribute('time'),
                'card_price_usd'     => (float) $room->getAttribute('card_price_usd'),
                'max_players'        => $room->getAttribute('max_players') !== null ? (int) $room->getAttribute('max_players') : null,
                'room_type_id'       => $room->getAttribute('room_type_id') !== null ? (string) $room->getAttribute('room_type_id') : null,
                'connection_type_id' => $room->getAttribute('connection_type_id') !== null ? (string) $room->getAttribute('connection_type_id') : null,
                'prize_pool_type_id' => $room->getAttribute('prize_pool_type_id') !== null ? (string) $room->getAttribute('prize_pool_type_id') : null,
            ])
            ->values()
            ->all();
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
     * @throws BingoCardGenerateException Si el rol no tiene soporte de monedas
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
     * @throws BingoCardGenerateException Si el rol no tiene soporte de monedas implementado
     */
    private function resolveEntityCurrencies(string $entityId, string $role): array
    {
        return match ($role) {
            // getAvailableCurrenciesIds filtra por status = 'ACTIVE' internamente
            'commerce'   => $this->commerceCurrencyRepository->getAvailableCurrenciesIds($entityId),
            // segundo parámetro true = solo relaciones activas (status = 1)
            'seller'     => $this->sellerCurrencyRepository->getAvailableCurrencies($entityId, true),
            // Subsidiary no tiene tabla de monedas propia — opera con las
            // monedas de su Commerce padre (la agencia hereda el catálogo
            // de la comercializadora que la administra).
            'subsidiary' => $this->resolveSubsidiaryCurrencies($entityId),
            default      => throw BingoCardGenerateException::unsupportedCurrencyRole($role),
        };
    }

    /**
     * Monedas activas del Commerce padre de una Subsidiary. Devuelve array
     * vacío (sin lanzar excepción) si la Subsidiary no tiene commerce_id —
     * el intersect() de arriba simplemente no dejará pasar ninguna moneda,
     * lo cual es el comportamiento correcto para una agencia mal configurada.
     */
    private function resolveSubsidiaryCurrencies(string $subsidiaryId): array
    {
        $commerceId = $this->subsidiaryRepository->getValueById($subsidiaryId, 'commerce_id');

        if (!$commerceId) {
            return [];
        }

        return $this->commerceCurrencyRepository->getAvailableCurrenciesIds($commerceId);
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
     * @throws BingoCardGenerateException Si la moneda, el commerce o la tasa no se encuentran.
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

        // 3. Sala vendible por este comercio y moneda habilitada — mismas
        //    validaciones que generate(), para que la vista previa nunca
        //    muestre un precio de algo que luego no se podrá vender.
        $this->findSellableRoom($roomId, $creatorUserId);
        $this->assertCountryAllowed($roomId, $userId, $countryId);

        $currencyId     = $this->currencies->getValueByCountry($countryId, 'id');
        $currencySymbol = $this->currencies->getValueByCountry($countryId, 'symbol');

        if (!$currencyId) {
            throw BingoCardGenerateException::currencyNotAvailable();
        }

        // 4-5. Tasa y precio — MISMOS helpers que generate()/buyFromGameRooms().
        $exchangeRate = $this->saleExchangeRate($currencyId, $creatorUserId);
        $price        = $this->resolveUnitCardPrice($roomId, $currencyId, $creatorUserId, $exchangeRate);

        // 6. Comisión: para el vendedor, la tasa EFECTIVA que se aplicará en
        //    la venta (especial de la sala o base, + bono progresivo).
        $commission = $role === 'seller'
            ? $this->commissionCalculation->sellerCommissionRate(
                (string) $this->sellerRepository->getValueByUserId($userId, 'id'),
                $roomId,
            )
            : $this->resolveEntityCommission($userId, $role);

        return [
            'card_price'        => $price['card_price'],
            'currency_symbol'   => (string) ($currencySymbol ?? ''),
            'exchange_rate'     => $exchangeRate,
            'is_custom_price'   => $price['is_custom_price'],
            'seller_commission' => $commission,
        ];
    }

    /**
     * Tasa de cambio de una venta: la del COMERCIO dueño del negocio (su tasa
     * propia si la tiene, si no la oficial — ver ExchangeRateDomainService),
     * redondeada a 2 decimales.
     *
     * Única fuente de la tasa para vista previa y cobro. Antes la vista
     * previa del vendedor la resolvía con el user_id del VENDEDOR (que nunca
     * tiene tasa propia → siempre la oficial) y sin redondear, mientras
     * generate() usaba la del comercio redondeada: si el comercio tenía tasa
     * propia, el precio que veía el vendedor no era el que se registraba.
     */
    private function saleExchangeRate(int|string $currencyId, int|string $rateOwnerUserId): float
    {
        return round(
            $this->exchangeRateDomain->getExchangeRateByCurrency((int) $currencyId, (int) $rateOwnerUserId),
            2,
        );
    }

    /**
     * Precio unitario del cartón en moneda local: precio personalizado del
     * comercio (bingo_card_prices) o, si no hay, precio base USD de la sala ×
     * tasa. Usado por vista previa y cobro.
     *
     * @return array{card_price: float, is_custom_price: bool}
     */
    private function resolveUnitCardPrice(string $roomId, int|string $currencyId, int|string $creatorUserId, float $exchangeRate): array
    {
        $customPrice = BingoCardPrice::byRoom($roomId)
            ->byCurrency($currencyId)
            ->where('creator_user_id', $creatorUserId)
            ->value('card_price');

        if ($customPrice !== null) {
            return [
                'card_price'      => (float) $this->priceCalculator->calculate($customPrice, true, $exchangeRate),
                'is_custom_price' => true,
            ];
        }

        $roomCardPrice = BingoRoomConfig::where('room_id', $roomId)->value('card_price_usd') ?? 0;

        return [
            'card_price'      => (float) $this->priceCalculator->calculate($roomCardPrice, false, $exchangeRate),
            'is_custom_price' => false,
        ];
    }

    /**
     * Sala en la que este comercio (y su red) puede vender: ACTIVE o
     * IN_PROGRESS, y propia del comercio o PUBLIC. Antes generate() solo
     * validaba el estado — una request armada a mano podía vender cartones en
     * la sala PRIVADA de otro comercio. Se responde "no disponible" (404) sin
     * distinguir "no existe" de "no es tuya", para no revelar salas ajenas.
     */
    private function findSellableRoom(string $roomId, int|string $creatorUserId): BingoRoom
    {
        $room = BingoRoom::select('id', 'uuid_code', 'status_id', 'prize_pool', 'creator_user_id')
            ->whereKey($roomId)
            ->whereHas('status', fn ($q) => $q->whereIn('code', ['ACTIVE', 'IN_PROGRESS']))
            ->accessibleBy((int) $creatorUserId)
            ->first();

        if (!$room) {
            throw BingoCardGenerateException::roomNotAvailable();
        }

        return $room;
    }

    /**
     * El país (y con él la moneda) debe estar entre los habilitados para esta
     * sala y este usuario — misma intersección que ya arma el selector de
     * países (getAvailableCountries). Antes solo se validaba en el frontend.
     */
    private function assertCountryAllowed(string $roomId, int|string $userId, string $countryId): void
    {
        $allowed = collect($this->getAvailableCountries($roomId, (string) $userId)['countries'] ?? [])
            ->map(fn ($country) => (string) (is_array($country) ? $country['id'] : $country->id));

        if (!$allowed->contains($countryId)) {
            throw BingoCardGenerateException::currencyNotAvailable();
        }
    }

    /**
     * Normaliza un número de comprobante para comparar duplicados: sin
     * espacios y en mayúsculas ("ab 123" y "AB123" son el mismo pago).
     */
    private function normalizeReceiptNumber(string $receiptNumber): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', trim($receiptNumber)));
    }

    /**
     * Resuelve el commerce_id y el creator_user_id (user_id del commerce) según el rol.
     *
     * - commerce: el propio commerce; creator_user_id = $userId.
     * - seller:   el commerce real del vendedor, resuelto vía
     *   Seller::resolveCommerce() — directo (manager_user_id → Commerce) o a
     *   través de una agencia intermedia (subsidiary_id → Subsidiary →
     *   Commerce). creator_user_id = user_id del Commerce resuelto, NUNCA
     *   manager_user_id crudo: resolveCommerce() es la fuente de verdad de la
     *   jerarquía (subsidiary_id prevalece sobre manager_user_id).
     *
     * @param string $userId ID del usuario autenticado.
     * @param string $role   Rol activo ('commerce' | 'seller').
     *
     * @return array{0: string, 1: string} [commerce_id, creator_user_id]
     *
     * @throws BingoCardGenerateException Si el commerce no existe.
     */
    private function resolveCommerceContext(string $userId, string $role): array
    {
        return match ($role) {
            'commerce' => (function () use ($userId): array {
                $commerceModel = Commerce::where('user_id', $userId)->first(['id', 'user_id', 'status']);

                if (!$commerceModel) {
                    throw BingoCardGenerateException::commerceNotFound();
                }

                // Un commerce BLOCKED/INACTIVE no debe poder comprar/vender
                // para sí mismo — mismo criterio que la rama 'seller' aplica
                // vía Seller::hasActiveHierarchy() más abajo.
                if ($commerceModel->status !== 'ACTIVE') {
                    throw BingoCardGenerateException::hierarchyNotActive();
                }

                return [$commerceModel->id, $userId];
            })(),
            'seller' => (function () use ($userId): array {
                $seller = Seller::where('user_id', $userId)->first(['id', 'manager_user_id', 'subsidiary_id', 'status']);

                if (!$seller || !$seller->hasActiveHierarchy()) {
                    throw BingoCardGenerateException::hierarchyNotActive();
                }

                $commerce = $seller->resolveCommerce();

                if (!$commerce) {
                    throw BingoCardGenerateException::commerceNotFound();
                }

                return [$commerce->id, $commerce->user_id];
            })(),
            default => throw BingoCardGenerateException::unsupportedCommerceRole($role),
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
     * @throws BingoCardGenerateException Si el rol no tiene soporte de comisión.
     */
    private function resolveEntityCommission(string $userId, string $role): float
    {
        $commission = match ($role) {
            'commerce'              => $this->commerceRepository->getValueByUserId($userId, 'commission'),
            'seller'                => $this->sellerRepository->getValueByUserId($userId, 'commission'),
            'superadmin', 'admin'   => 0,
            default                 => throw BingoCardGenerateException::unsupportedCommissionRole($role),
        };

        return (float) ($commission ?? 0);
    }

    /**
     * Genera cartones de bingo para un jugador, operado por un vendedor.
     *
     * @param BingoCardGenerateDTO $dto
     * @return array{type: string, message: string, playerLink: string}
     * @throws BingoCardGenerateException
     */
    public function generate(BingoCardGenerateDTO $dto): array
    {
        // 1. Obtener seller autenticado
        // resolveCommerce() resuelve el Commerce real sin importar si el
        // seller reporta directo (manager_user_id) o vía una agencia
        // intermedia (subsidiary_id → Subsidiary → Commerce) — ver
        // Seller::resolveCommerce(). Antes se asumía siempre manager_user_id
        // → Commerce directo, lanzando 'Comercio asociado no encontrado'
        // para cualquier seller de una Subsidiary.
        $seller = Seller::select(['id', 'commission', 'manager_user_id', 'subsidiary_id', 'status'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$seller) {
            throw BingoCardGenerateException::sellerNotFound();
        }

        $commerceModel = $seller->resolveCommerce();

        if (!$commerceModel) {
            throw BingoCardGenerateException::commerceNotFound();
        }

        // Bloquear/desactivar el Commerce o la Subsidiary padre no
        // desactivaba automáticamente a sus Sellers subordinados — sin esta
        // validación, un seller ACTIVE seguía vendiendo con normalidad
        // aunque su comercio/agencia estuviera BLOCKED/INACTIVE.
        if (!$seller->hasActiveHierarchy()) {
            throw BingoCardGenerateException::hierarchyNotActive();
        }

        $creatorUser = $commerceModel->user_id;
        $commerce    = $commerceModel->id;

        // 2. Sala vendible por este comercio (estado + propia o PUBLIC).
        $room = $this->findSellableRoom($dto->roomId, $creatorUser);

        // 3. País/moneda habilitados para esta sala y este vendedor.
        $this->assertCountryAllowed((string) $room->id, (string) Auth::id(), $dto->countryId);

        $currency = $this->currencies->getValueByCountry($dto->countryId, 'id');

        if (!$currency) {
            throw BingoCardGenerateException::currencyNotAvailable();
        }

        // 4-5. Tasa y precio — MISMOS helpers que la vista previa (getCardPrice).
        $exchangeRate = $this->saleExchangeRate($currency, $creatorUser);
        $cardPrice    = $this->resolveUnitCardPrice((string) $room->id, $currency, $creatorUser, $exchangeRate)['card_price'];

        // El vendedor cobró según el precio que vio: si cambió desde entonces
        // (tasa o precio personalizado actualizado), no se registra la venta a
        // un monto distinto — se le pide revisar el nuevo precio.
        if ($dto->expectedCardPrice !== null && abs($dto->expectedCardPrice - $cardPrice) > 0.005) {
            throw BingoCardGenerateException::priceChanged($dto->expectedCardPrice, $cardPrice);
        }

        $cardPriceUsd = $this->toUsdAmount($cardPrice, $exchangeRate);

        $receiptNumber = $this->normalizeReceiptNumber($dto->receiptNumber);

        // 6. Buscar jugador existente en la sala — solo de la red comercial
        //    del vendedor (mismo criterio que resolveOrCreatePlayer()): en una
        //    sala PUBLIC el mismo teléfono puede pertenecer a otro comercio y
        //    su room_user no debe recibir estos cartones.
        $existingRoomUser = ExternalPlayer::select('id', 'link')
            ->where('game_id', 1)
            ->where('room_id', $room->id)
            ->where('phone', $dto->playerPhone)
            ->whereIn('seller_user_id', $this->externalPlayerLink->actorNetworkUserIds(Auth::id()))
            ->value('room_user_id');

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
            'exchangeRate'  => $exchangeRate,
            'receiptNumber' => $receiptNumber,
            'receiptImage'  => $dto->receiptImage,
        ];

        if ($existingRoomUser) {
            $cardOrder['roomUser'] = $existingRoomUser;
        }

        // 7. Comprobante único por red comercial + transacción atómica.
        //    El lock serializa dos envíos con el mismo comprobante (doble clic,
        //    reintento, dos vendedores a la vez) — sin él ambos podían pasar el
        //    chequeo de duplicado antes de que ninguno comiteara.
        $networkUserIds = $commerceModel->networkUserIds();
        $lock = Cache::lock('bingo:receipt:' . $commerce . ':' . sha1($receiptNumber), 30);

        if (!$lock->get()) {
            throw BingoCardGenerateException::duplicateReceipt();
        }

        try {
            $this->assertReceiptNotUsed($receiptNumber, $networkUserIds);

            $result = $this->runSaleTransaction($cardOrder);
        } finally {
            $lock->release();
        }

        // 8. Emitir evento de actualización de sala via WebSocket (fuera de la transacción)
        //    Recarga los datos frescos después del commit para garantizar consistencia.
        $this->broadcastRoomUpdated((string) $room->id);

        // 9. Actividad en vivo para admin, el Commerce y — si el seller es de
        //    agencia — la Subsidiary. Antes solo buyFromGameRooms() emitía
        //    BingoCardSold, así que la venta principal del punto de venta
        //    (vendedor → jugador externo) nunca llegaba a los dashboards.
        //    Best-effort: la venta ya está comprometida, un fallo de
        //    broadcast no debe convertirse en error para el vendedor.
        try {
            $subsidiaryUserId = $seller->subsidiary_id
                ? \App\Models\Roles\Subsidiary::where('id', $seller->subsidiary_id)->value('user_id')
                : null;

            broadcast(new BingoCardSold(
                roomId: (string) $room->id,
                roomName: (string) $room->uuid_code,
                quantity: (int) $dto->cardCount,
                amountUsd: (float) $cardOrder['saleAmountUsd'],
                creatorUserId: (string) $creatorUser,
                subsidiaryUserId: $subsidiaryUserId ? (string) $subsidiaryUserId : null,
            ));
        } catch (\Throwable $e) {
            Log::warning('BingoCardSold: no se pudo emitir la venta del punto de venta', [
                'room_id' => (string) $room->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Un comprobante solo puede respaldar UNA venta vigente (pendiente o
     * aprobada) dentro de la red comercial. Uno rechazado/cancelado sí puede
     * reutilizarse (reenvío corregido de una venta anulada).
     *
     * @param string[] $networkUserIds
     */
    private function assertReceiptNotUsed(string $receiptNumber, array $networkUserIds): void
    {
        $used = BingoCardPayment::query()
            ->where('transaction_reference', $receiptNumber)
            ->whereIn('payment_status', ['PENDING', 'APPROVED'])
            ->whereIn('seller_user_id', $networkUserIds)
            ->exists();

        if ($used) {
            throw BingoCardGenerateException::duplicateReceipt();
        }
    }

    /**
     * @return array{type: string, message: string, playerLink: string|null, paymentStatus: string}
     */
    private function runSaleTransaction(array $cardOrder): array
    {
        return DB::transaction(function () use ($cardOrder) {
            // Resolver/crear el ExternalPlayer ANTES de generar los cartones —
            // external_players.room_user_id ahora es nullable/informativo
            // (ver create_external_players_table), así que no depende de que
            // el BingoRoomUser ya exista. Necesitamos su ID ya en este punto
            // para vincular cada cartón individual vía
            // ExternalPlayerParticipation (ver BingoCardGenerationService),
            // sin lo cual todos los compradores externos de este vendedor en
            // esta sala quedarían indistinguibles entre sí.
            $cardOrder = $this->externalPlayerLink->resolveOrCreatePlayer($cardOrder);

            $roomUser = $this->cardGenerationService->generateCards($cardOrder);

            if (!$roomUser) {
                throw BingoCardGenerateException::cardGenerationFailed();
            }

            $this->externalPlayerLink->updateRoomUser($cardOrder['externalPlayerId'], $roomUser['room_user_id']);

            $playerLink = $this->externalPlayerLink->resolveGameUrl($cardOrder['externalPlayerId']);

            // Venta a comprador externo: el pago nace PENDING y los cartones
            // no son jugables hasta que el comercio apruebe el comprobante —
            // el vendedor debe saberlo para avisarle al comprador.
            return [
                'type'          => 'success',
                'message'       => 'Cartones generados. El pago quedó en revisión: el comprador podrá jugar cuando el comercio apruebe el comprobante.',
                'playerLink'    => $playerLink,
                'paymentStatus' => 'PENDING',
            ];
        });
    }

    /**
     * Compra de cartones desde la vista game-rooms (commerce/seller para su player).
     *
     * Los datos del jugador (teléfono, nombre) vienen del contexto del commerce/seller
     * autenticado — no se requiere formulario de datos del jugador.
     *
     * @param \App\Domains\Games\Bingo\DTOs\Room\BingoRoomPlayerBuyDTO $dto
     * @return array{type: string, message: string, prize_pool?: float, card_count?: int}
     * @throws BingoCardGenerateException
     */
    public function buyFromGameRooms(\App\Domains\Games\Bingo\DTOs\Room\BingoRoomPlayerBuyDTO $dto): array
    {
        $userId = (string) $this->userContext->resolveUserId();
        $role   = $this->roleUserService->getActiveUserRolePrefix((int) $userId);

        // 1. Resolver commerce responsable según rol
        [$commerceId, $creatorUserId] = $this->resolveCommerceContext($userId, $role);

        // 2. Validar sala — permite ACTIVE o IN_PROGRESS
        $room = BingoRoom::select('id', 'uuid_code', 'status_id', 'prize_pool', 'creator_user_id')
            ->with(['status:id,code', 'config:id,room_id,card_price_usd,room_type_id', 'config.roomType:id,code'])
            ->whereHas('status', fn($q) => $q->whereIn('code', ['ACTIVE', 'IN_PROGRESS']))
            ->where('id', $dto->roomId)
            ->first();

        if (!$room) {
            throw BingoCardGenerateException::roomNotAvailable();
        }

        // Defensa en profundidad: si la sala es privada, exige haber verificado
        // código/contraseña antes (POST /bingo-rooms/{room}/verify-access).
        $this->bingoRoomCrud->assertRoomAccessVerified($room);

        // 3. Moneda del país seleccionado
        $currencyId = $this->currencies->getValueByCountry($dto->countryId, 'id');

        if (!$currencyId) {
            throw BingoCardGenerateException::currencyNotAvailable();
        }

        // 4-5. Tasa y precio — MISMOS helpers que la vista previa. La tasa es
        //      la del COMERCIO ($creatorUserId), no la del usuario que compra:
        //      antes se usaba $userId, que para un seller es su propio user
        //      (sin tasa propia → siempre la oficial), distinta de la del comercio.
        $exchangeRate = $this->saleExchangeRate($currencyId, $creatorUserId);
        $cardPrice    = $this->resolveUnitCardPrice((string) $room->id, $currencyId, $creatorUserId, $exchangeRate)['card_price'];
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
                throw BingoCardGenerateException::cardGenerationFailed();
            }

            // Sin resolveOrCreatePlayer(): aquí no hay comprador externo (los
            // cartones son del propio commerce/seller, sin teléfono ni nombre).
            // Antes se llamaba con playerPhone '' y se descartaba el resultado,
            // creando un ExternalPlayer + UserPurchaseCode vacíos por sala/red
            // sin vínculo con ningún cartón.

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
        // Si quien vendió es un Seller que reporta a una agencia (subsidiary_id),
        // también se notifica a esa agencia — antes solo se notificaba al
        // creador de la sala (el Commerce), dejando a las agencias sin
        // actividad en vivo pese a que nunca crean salas (ver
        // SubsidiaryDashboardContextService, dashboard sin tiempo real hasta
        // este fix).
        $subsidiaryUserId = null;
        if ($role === 'seller') {
            $subsidiaryId = Seller::where('user_id', $userId)->value('subsidiary_id');
            if ($subsidiaryId) {
                $subsidiaryUserId = \App\Models\Roles\Subsidiary::where('id', $subsidiaryId)->value('user_id');
            }
        }

        broadcast(new BingoCardSold(
            roomId: (string) $room->id,
            roomName: (string) $room->uuid_code,
            quantity: (int) $cardOrder['cardCount'],
            amountUsd: (float) $cardOrder['saleAmountUsd'],
            creatorUserId: (string) $creatorUserId,
            subsidiaryUserId: $subsidiaryUserId ? (string) $subsidiaryUserId : null,
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
            // Commerce real vía resolveCommerce() (fuente de verdad de la
            // jerarquía, cubre sellers directos y de agencia).
            'seller' => (int) (Seller::where('user_id', $userId)
                ->first(['id', 'manager_user_id', 'subsidiary_id'])
                ?->resolveCommerce()
                ?->user_id
                ?? throw BingoCardGenerateException::commerceNotFound()),
            default => throw BingoCardGenerateException::invalidManagerRole(),
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
            throw BingoCardGenerateException::roomNotAvailable();
        }

        // Obtener la billetera activa del jugador
        $activeWallets = $this->walletRepository->getActiveWalletsByUser((string)$userId, ['currency_id']);

        if ($activeWallets->isEmpty()) {
            throw BingoCardGenerateException::noActiveWallet();
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
            throw BingoCardGenerateException::unauthorized();
        }

        $room = BingoRoom::with('config')->findOrFail($roomId);

        // Defensa en profundidad: si la sala es privada, exige haber verificado
        // código/contraseña antes (POST /bingo-rooms/{room}/verify-access).
        $this->bingoRoomCrud->assertRoomAccessVerified($room);

        $variant = $room->config?->game_variant ?? BingoVariant::BINGO_75;

        $customCount = $customMatrices !== null ? count($customMatrices) : 0;
        $movedFreeSpaceCount = 0;

        if ($customCount > 0) {
            if (!$room->config?->allow_custom_card) {
                throw BingoCardGenerateException::customCardsNotAllowed();
            }

            if ($customCount > $cardCount) {
                throw BingoCardGenerateException::customCardsExceedCount();
            }

            if ($room->config->custom_card_mode === 'every_x') {
                $everyX = (int) ($room->config->custom_card_every_x ?? 0);
                $maxAllowed = $this->maxCustomCardsAllowed($everyX, $cardCount);
                if ($customCount > $maxAllowed) {
                    throw BingoCardGenerateException::customCardsExceedEveryXLimit($maxAllowed);
                }
            }

            $allowMoveFreeSpace = $room->config?->move_free_space_mode !== null
                && $variant === BingoVariant::BINGO_75;

            foreach ($customMatrices as $i => $matrix) {
                $error = BingoVariant::validateCustomMatrix($variant, $matrix, $allowMoveFreeSpace);
                if ($error !== null) {
                    throw BingoCardGenerateException::customCardMatrixInvalid($i + 1, $error);
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
            throw BingoCardGenerateException::noActiveWallet();
        }

        $playerWallet = $activeWallets->first();
        $currencyId = $playerWallet->currency_id;

        // Verificación optimista (fail-fast antes de generar cartones); la
        // verificación autoritativa ocurre dentro de recordCardPurchase() bajo
        // lockForUpdate(). Se considera bono + saldo real, ya que la compra
        // consume primero el bono.
        if (! $playerWallet->hasSufficientPlayableBalance($totalAmount)) {
            $currentBalance = (float) $playerWallet->getPlayableBalance();
            throw BingoCardGenerateException::insufficientBalance($currentBalance, $totalAmount);
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

        // Notificación persistente de la compra — no interrumpe la compra si
        // falla (misma política que el resto de side-effects post-transacción
        // en este método: broadcasts best-effort).
        try {
            $this->notificationService->createCardPurchaseNotification(
                userId:         (string) $userId,
                roomUuidCode:   (string) $room->uuid_code,
                cardCount:      $cardCount,
                amount:         $totalAmount,
                currencySymbol: (string) $priceInfo['currency_symbol'],
                roomId:         (string) $room->id,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo crear la notificación de compra de cartones', [
                'user_id' => $userId,
                'room_id' => $roomId,
                'error'   => $e->getMessage(),
            ]);
        }

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
            throw BingoCardGenerateException::invalidExchangeRate();
        }

        return round($amount / $exchangeRate, 3);
    }
}

