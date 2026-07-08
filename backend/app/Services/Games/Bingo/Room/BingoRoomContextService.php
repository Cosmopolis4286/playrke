<?php

namespace App\Services\Games\Bingo\Room;

use App\Domains\Games\Bingo\DTOs\Room\BingoGameRoomsDTO;
use App\Domains\Games\Bingo\DTOs\Room\BingoRoomSummaryDTO;
use App\Models\ExchangeRate;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Pagination\LengthAwarePaginator;

final class BingoRoomContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly \App\Contracts\Games\Bingo\Room\BingoRoomRepositoryInterface $bingoRooms,
        private readonly \App\Services\Currency\CurrencyService $currencyService,
        private readonly \App\Contracts\GameRepositoryInterface $games,
        private readonly \App\Contracts\Roles\Seller\SellerRepositoryInterface $sellerRepository,
        private readonly \App\Contracts\TimeZoneRepositoryInterface $timeZones,
        private readonly \App\Contracts\Games\Bingo\Pattern\BingoPatternRepositoryInterface $bingoPatterns,
        private readonly \App\Contracts\Games\Common\PrizePool\PrizePoolTypeRepositoryInterface $prizePoolTypes,
        private readonly \App\Contracts\Games\Common\GameMode\GameModeRepositoryInterface $gameModes,
        private readonly \App\Contracts\Games\Common\Room\RoomTypeRepositoryInterface $roomTypes,
        private readonly \App\Contracts\UserRepositoryInterface $userRepository,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencyRepository,
        private readonly \App\Services\Games\Bingo\Card\Generate\BingoCardGenerateService $generateService,
        private readonly \App\Services\Games\Bingo\Config\BingoConfigContextService $bingoConfigContext,
        private readonly \App\Services\ExchangeRate\CurrencyExchangeRateService $currencyExchangeRate,
    ) {}

    /**
     * Resolver contexto para BingoRoom.
     *
     * @param string $context
     * @param int|null $userId
     * @return mixed
     * @throws DomainException Si el contexto no es válido
     */
    public function context(string $context, ?int $userId): mixed
    {
        return match ($context) {
            'fetch-summary', 'fetch-game-rooms' => $this->fetchSummaryGameRooms($userId),
            'summary'     => $this->summaryGameRooms($userId),
            'game-rooms'  => $this->viewGameRooms($userId),
            default       => throw new DomainException('No existe el contexto: ' . $context),
        };
    }

    /**
     * Resolver listado de salas de bingo.
     *
     * @param int|null $userId ID del usuario
     * @return array Listado de salas con paginación
     * @throws DomainException Si no se pueden resolver las salas de bingo
     */
    private function resolveSummaryGameRooms(?int $userId): array
    {
        $labels  = ['Código', 'Estado', 'Tipo', 'Modo', 'Tipo Bote', 'Moneda(s)', 'Jugadores', 'Cartones', 'Premio', 'Inicio'];
        $rowKeys = ['id', 'status_badge', 'config.roomType.label', 'config.gameMode.label', 'config.prizePoolType.label', 'config.allowed_currencies', 'total_players', 'total_cards', 'prize_pool', 'start_info'];

        // Superadmin y admin ven todas las salas; commerce/customer solo las suyas
        $isGlobalAdmin = $this->userContext->userHasRole('superadmin')
            || $this->userContext->userHasRole('admin');

        $conditions = (!$isGlobalAdmin && $userId)
            ? ['creator_user_id' => $userId]
            : [];

        $source = $this->bingoRooms->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['config', 'config.roomType', 'config.gameMode', 'config.prizePoolType', 'status'],
            conditions: $conditions,
            fields: ['id', 'uuid_code', 'status_id', 'creator_user_id', 'total_players', 'total_cards', 'prize_pool', 'created_at']
        );

        $collection = $source instanceof LengthAwarePaginator
            ? $source->getCollection()
            : collect($source);

        $pagination = $source instanceof LengthAwarePaginator
            ? [
                'total'        => $source->total(),
                'per_page'     => $source->perPage(),
                'current_page' => $source->currentPage(),
                'last_page'    => $source->lastPage(),
                'from'         => $source->firstItem(),
                'to'           => $source->lastItem(),
            ]
            : null;

        $rooms = $this->processCollectionTableData($collection, $labels, $rowKeys);

        $authUser  = \Illuminate\Support\Facades\Auth::user();
        $authUserId = $authUser ? (string) $authUser->id : null;
        $isAdmin    = $authUser && $authUser->hasRole(['admin', 'superadmin']);

        $currencyMap = $userId
            ? collect($this->currencyService->getAvailableCurrencies($userId, true))->pluck('iso_code', 'id')
            : collect();

        // Enriquecer cada row con campos meta para filtros client-side
        foreach ($rooms['rows'] as $i => &$row) {
            $model = $collection->values()->get($i);
            $config = $model?->config;

            $row['unix_date']  = $config?->unix_date ?? null;
            $row['from_house'] = $config?->from_house ?? 'N';
            $row['status']     = $model?->status?->code ?? null;
            $row['start_mode'] = $config?->start_mode ?? 'SCHEDULED';

            // can_activate_now: sala MANUAL + ACTIVE + (creador o admin)
            $isManual   = ($config?->start_mode ?? 'SCHEDULED') === 'MANUAL';
            $isActive   = ($model?->status?->code ?? '') === 'ACTIVE';
            $isCreator  = $authUserId && (string) $model?->creator_user_id === $authUserId;
            $row['can_activate_now'] = $isManual && $isActive && ($isCreator || $isAdmin);

            // Quitar prefijo "Bote " del tipo de bote
            if (isset($row['config.prizePoolType.label'])) {
                $row['config.prizePoolType.label'] = preg_replace('/^Bote\s+/i', '', $row['config.prizePoolType.label']);
            }

            // Formatear columna Cartones: vendidos + mínimo si aplica
            $minCards = $config?->min_cards_to_start;
            if ($minCards !== null && $minCards > 0) {
                $row['total_cards'] = ($row['total_cards'] ?? 0) . ' / mín: ' . $minCards;
            }

            // Columna "Moneda(s)": mostrar iso_codes; campo separado con ids para filtrar
            $currencyIds = (array) ($row['config_allowed_currencies'] ?? []);
            $isoCodes = array_values(array_map(fn($id) => $currencyMap[$id] ?? $id, $currencyIds));

            $row['config_allowed_currencies'] = implode(', ', $isoCodes);
            $row['currency_ids'] = $currencyIds;
        }
        unset($row);

        return ['rooms' => $rooms, 'pagination' => $pagination];
    }

    /**
     * Obtener las salas de bingo.
     *
     * @param int $userId ID del usuario
     * @return array
     */
    private function fetchSummaryGameRooms(int $userId): array
    {
        return $this->resolveSummaryGameRooms($userId)['rooms']['rows'] ?? [];
    }

    /**
     * Resolver contexto para el listado de salas de bingo.
     *
     * @param int $userId ID del usuario
     * @return BingoRoomSummaryDTO
     *
     * @throws DomainException Si no se puede resolver el contexto
     */
    private function summaryGameRooms(int $userId): BingoRoomSummaryDTO
    {
        $commerceData = $this->resolveSummaryGameRooms($userId);

        $currencies = $this->currencyService->getAvailableCurrencies($userId, true);

        $currenciesWithActiveRate = ExchangeRate::active()
            ->whereIn('currency_id', collect($currencies)->pluck('id'))
            ->pluck('currency_id')
            ->unique()
            ->values()
            ->all();

        return new BingoRoomSummaryDTO(
            rooms: $commerceData['rooms'],
            pagination: $commerceData['pagination'],
            currencies: $currencies,
            currenciesWithActiveRate: $currenciesWithActiveRate,
            bingoPatterns: $this->bingoPatterns->getByUserAndDefault($userId, ['id', 'name'], 'ACTIVE'),
            prizePoolTypes: $this->prizePoolTypes->getActiveTypes(),
            gameModes: $this->gameModes->getActiveGameModes(),
            roomTypes: $this->roomTypes->getActiveTypes()
        );
    }

    /**
     * Resolver vista para el listado de salas de juego de bingo.
     *
     * Retorna salas activas del usuario con config (fecha, precio, unix_date)
     * para que el frontend construya los cards con countdown.
     * Si el usuario tiene rol 'player', solo devuelve salas PUBLIC y aplica tasa de cambio.
     *
     * @param int $userId ID del usuario
     * @return BingoGameRoomsDTO
     *
     * @throws DomainException Si no se puede resolver el contexto
     */
    private function viewGameRooms(int $userId): BingoGameRoomsDTO
    {
        $today = now()->toDateString();

        // Verificar roles del usuario
        $isPlayer          = $this->userContext->userHasRole('player');
        $isCommerceCustomer = $this->userContext->userHasRole('commerce') || $this->userContext->userHasRole('customer');
        $isAdmin           = $this->userContext->userHasRole('admin') || $this->userContext->userHasRole('superadmin');

        // Usar el nuevo método con filtrado por rol
        $rooms = $this->bingoRooms->getActiveRoomsForViewWithRoleFilter($userId, $today, $isPlayer, $isCommerceCustomer, $isAdmin);

        $timestamp = now()->valueOf(); // ms

        $dateTimeRooms = [
            'timestamp' => $timestamp,
            'rooms'     => $rooms->map(fn($room) => [
                'id'          => (string) $room->id,
                'config'      => [
                    'unix_date' => $room->config?->unix_date,
                    'room_type' => $room->config?->roomType?->code ?? 'PUBLIC'
                ],
                'players_room' => $room->players
                    ->where('user_id', $userId)
                    ->map(fn($ru) => ['ctns' => $ru->cards_purchased])
                    ->values()
                    ->all(),
            ])->values()->all(),
        ];

        // Commerce/seller/admin ven en USD (manejan múltiples monedas).
        // Solo players usan la moneda de su país (moneda única fija).
        $exchangeRate = 1.0;
        $currencySym = 'USD';

        if ($isPlayer) {
            $currentUserId = $this->userContext->resolveUserId();
            $countryId = $this->userRepository->getValueById($currentUserId, 'country_id');

            if ($countryId) {
                $playerCurrency = $this->currencyRepository
                    ->getByCountry($countryId, ['id', 'iso_code', 'symbol'])
                    ->first();

                if ($playerCurrency) {
                    $currencySym = $playerCurrency->symbol;
                    $exchangeRate = $this->currencyExchangeRate->getActiveRate($playerCurrency->id);
                }
            }
        }

        return new BingoGameRoomsDTO(
            rooms: $rooms,
            exchangeRate: $exchangeRate,
            currencySym: $currencySym,
            userToken: null,
            dateTimeRooms: $dateTimeRooms,
            cardPrices: $isPlayer ? $this->getPlayerCardPriceForContext($userId) : null,
            bingoRules: $this->bingoConfigContext->getBingoRules($userId),
            imageBingoRules: $this->bingoConfigContext->getImageBingoRules($userId),
        );
    }

    /**
     * Obtener precio del cartón para el contexto (para cada sala)
     *
     * @param int $userId ID del usuario
     * @return array|null
     */
    private function getPlayerCardPriceForContext(int $userId): ?array
    {
        try {
            $isCommerceCustomer = $this->userContext->userHasRole('commerce') || $this->userContext->userHasRole('customer');
            $rooms = $this->bingoRooms->getActiveRoomsForViewWithRoleFilter($userId, now()->toDateString(), true, $isCommerceCustomer);

            if ($rooms->isEmpty()) {
                return null;
            }

            $roomPrices = [];
            foreach ($rooms as $room) {
                $roomPrices[$room->id] = $this->generateService->getPlayerCardPrice((string) $room->id, $userId);
            }

            return $roomPrices;
        } catch (\Throwable) {
            return null;
        }
    }
}
