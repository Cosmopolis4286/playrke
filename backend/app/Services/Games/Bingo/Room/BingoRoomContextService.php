<?php

namespace App\Services\Games\Bingo\Room;

use App\DTOs\Games\Bingo\Room\BingoGameRoomsDTO;
use App\DTOs\Games\Bingo\Room\BingoRoomSummaryDTO;
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
            'fetch-summary' => $this->fetchSummaryGameRooms($userId),
            'summary' => $this->summaryGameRooms($userId),
            'fetch-game-rooms' => $this->fetchSummaryGameRooms($userId),
            'game-rooms' => $this->viewGameRooms($userId),
            default => throw new DomainException('No existe el contexto: ' . $context),
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
        $labels  = ['Código', 'Tipo', 'Jugadores', 'Premio', 'Creación', 'Estado'];
        $rowKeys = ['id', 'config.roomType.label', 'total_players', 'prize_pool', 'created_at', 'status_badge'];

        $conditions = $userId
            ? ['creator_user_id' => $userId]
            : [];

        $source = $this->bingoRooms->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['config:room_id,room_type_id,unix_date'],
            conditions: $conditions,
            fields: ['id', 'uuid_code', 'status_id', 'creator_user_id', 'total_players', 'prize_pool', 'created_at']
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

        // Enriquecer cada row con unix_date (campo meta, no visible como columna)
        $unixDates = $collection->pluck('config.unix_date')->values()->all();
        foreach ($rooms['rows'] as $i => &$row) {
            $row['unix_date'] = $unixDates[$i] ?? null;
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

        return new BingoRoomSummaryDTO(
            rooms: $commerceData['rooms'],
            pagination: $commerceData['pagination'],
            currencies: $currencies,
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

        // Verificar si el usuario tiene rol 'player'
        $isPlayer = $this->userContext->userHasRole('player');

        // Usar el nuevo método con filtrado por rol
        $rooms = $this->bingoRooms->getActiveRoomsForViewWithRoleFilter($userId, $today, $isPlayer);

        $timestamp = now()->valueOf(); // ms

        $dateTimeRooms = [
            'timestamp' => $timestamp,
            'rooms'     => $rooms->map(fn($room) => [
                'id'          => (string) $room->id,
                'config'      => [
                    'unix_date' => $room->config?->unix_date,
                    'room_type' => $room->config?->roomType?->code ?? 'PUBLIC'
                ],
                'players_room' => null,
            ])->values()->all(),
        ];

        // Configuración por defecto
        $exchangeRate = 1.0;
        $currencySym = 'USD';
        $currency = null;

        // Si es jugador, obtener tasa de cambio y moneda
        if ($isPlayer) {
            // Obtener el país del usuario usando el repositorio
            $currentUserId = $this->userContext->resolveUserId();
            $countryId = $this->userRepository->getValueById($currentUserId, 'country_id');
            
            if ($countryId) {
                // Obtener la moneda del país del jugador usando el repositorio de monedas
                $playerCurrencies = $this->currencyRepository->getByCountry($countryId, ['id', 'iso_code', 'symbol']);
                $playerCurrency = $playerCurrencies->first();
                
                if ($playerCurrency) {
                    $currency = $playerCurrency->iso_code;
                    $currencySym = $playerCurrency->symbol;
                    
                    // Obtener tasa de cambio (USD a moneda del jugador)
                    $exchangeRate = $this->getPlayerExchangeRate($playerCurrency->id);
                }
            }
        }

        return new BingoGameRoomsDTO(
            rooms: $rooms,
            exchangeRate: $exchangeRate,
            currencySym: $currencySym,
            currency: $currency,
            creator: null,
            player: null,
            userToken: null,
            customerId: null,
            creatorUser: null,
            dateTimeRooms: $dateTimeRooms,
            cardPrices: $isPlayer ? $this->getPlayerCardPriceForContext($userId) : null,
        );
    }

    /**
     * Obtener tasa de cambio para jugador (USD a moneda local)
     *
     * @param int $currencyId ID de la moneda del jugador
     * @return float Tasa de cambio
     */
    private function getPlayerExchangeRate(int $currencyId): float
    {
        // Buscar tasa de cambio general para la moneda
        $exchangeRate = \App\Models\ExchangeRate::where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderBy('created_at', 'desc')
            ->first();

        return $exchangeRate ? (float) $exchangeRate->amount : 1.0;
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
            // Obtener todas las salas activas para el jugador
            $rooms = $this->bingoRooms->getActiveRoomsForViewWithRoleFilter($userId, now()->toDateString(), true);
            
            if ($rooms->isEmpty()) {
                return null;
            }
            
            // Obtener precios para cada sala
            $roomPrices = [];
            foreach ($rooms as $room) {
                $roomPrices[$room->id] = $this->generateService->getPlayerCardPrice($room->id, $userId);
            }
            
            return $roomPrices;
        } catch (\Exception $e) {
            return null;
        }
    }
}
