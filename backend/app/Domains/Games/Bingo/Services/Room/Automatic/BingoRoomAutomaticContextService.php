<?php

namespace App\Domains\Games\Bingo\Services\Room\Automatic;

use App\Domains\Games\Bingo\DTOs\Room\Automatic\BingoRoomAutomaticSummaryDTO;
use App\Models\ExchangeRate;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Pagination\LengthAwarePaginator;

final class BingoRoomAutomaticContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly \App\Domains\Games\Bingo\Contracts\Room\BingoRoomAutomaticRepositoryInterface $bingoRooms,
        private readonly \App\Services\Currency\CurrencyService $currencyService,
        private readonly \App\Contracts\GameRepositoryInterface $games,
        private readonly \App\Contracts\Roles\Seller\SellerRepositoryInterface $sellerRepository,
        private readonly \App\Contracts\TimeZoneRepositoryInterface $timeZones,
        private readonly \App\Domains\Games\Bingo\Contracts\Pattern\BingoPatternRepositoryInterface $bingoPatterns,
        private readonly \App\Contracts\Games\Common\PrizePool\PrizePoolTypeRepositoryInterface $prizePoolTypes,
        private readonly \App\Contracts\Games\Common\ConnectionType\ConnectionTypeRepositoryInterface $connectionTypes,
        private readonly \App\Contracts\Games\Common\Room\RoomTypeRepositoryInterface $roomTypes,
    ) {}

    /**
     * Resolver contexto para BingoRoomAutomatic.
     *
     * @param string $context
     * @param int|null $userId
     * @return mixed
     * @throws DomainException Si el contexto no es válido
     */
    public function context(string $context, ?int $userId): mixed
    {
        return match ($context) {
            'fetch' => $this->fetchGameRooms($userId),
            'summary' => $this->summaryGameRooms($userId),
            default => throw new DomainException('No existe el contexto: ' . $context),
        };
    }

    /**
     * Resolver listado de salas de bingo automaticas.
     *
     * @param int|null $userId ID del usuario para filtrar
     * @param int $page Página solicitada (1-based)
     * @param int $perPage Registros por página
     * @param array{
     *     status?: string,
     *     room_type_id?: string,
     *     game_mode_id?: string,
     *     prize_pool_type_id?: string,
     *     currency_id?: string,
     * } $filters Filtros server-side (ver BingoRoomAutomaticRepository::paginateSummaryGameRooms)
     * @return array Listado de salas con paginación
     * @throws DomainException Si no se pueden resolver las salas de bingo
     */
    private function resolveGameRooms(?int $userId, int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $labels  = ['Código', 'Estado', 'Días', 'Hora', 'Tipo de Sala', 'Modo de Juego', 'Tipo Bote', 'Moneda(s)'];
        $rowKeys = ['id', 'status_badge', 'days_description', 'formatted_time', 'roomType.label', 'connectionType.label', 'prizePoolType.label', 'allowed_currencies'];

        $conditions = $userId
            ? ['creator_user_id' => $userId]
            : [];

        $source = $this->bingoRooms->paginateSummaryGameRooms(
            perPage: $perPage,
            page: $page,
            conditions: $conditions,
            filters: $filters,
            relations: ['roomType:id,label', 'connectionType:id,label', 'prizePoolType:id,label'],
            fields: ['id', 'room_type_id', 'connection_type_id', 'prize_pool_type_id', 'days', 'time', 'allowed_currencies', 'creator_user_id', 'created_at', 'status']
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

        $currencyMap = $userId
            ? collect($this->currencyService->getAvailableCurrencies($userId, true))->pluck('iso_code', 'id')
            : collect();

        // Columna "Moneda(s)": mostrar iso_codes; campo separado con ids para filtrar
        foreach ($rooms['rows'] as &$row) {
            $currencyIds = (array) ($row['allowed_currencies'] ?? []);
            $isoCodes = array_values(array_map(fn($id) => $currencyMap[$id] ?? $id, $currencyIds));

            $row['allowed_currencies'] = implode(', ', $isoCodes);
            $row['currency_ids'] = $currencyIds;
        }
        unset($row);

        return ['rooms' => $rooms, 'pagination' => $pagination];
    }

    /**
     * Obtener las salas de bingo automaticas.
     *
     * @param int $userId ID del usuario para filtrar
     * @return array
     */
    private function fetchGameRooms(int $userId): array
    {
        return $this->resolveGameRooms($userId)['rooms']['rows'] ?? [];
    }

    /**
     * Refetch server-side del listado de salas automáticas (paginación/
     * filtros de Room/Automatic/Summary tras la carga inicial vía Inertia).
     *
     * @param array{
     *     status?: string,
     *     room_type_id?: string,
     *     game_mode_id?: string,
     *     prize_pool_type_id?: string,
     *     currency_id?: string,
     * } $filters
     */
    public function summaryDataForRefetch(int $userId, int $page, int $perPage, array $filters): array
    {
        return $this->resolveGameRooms($userId, $page, $perPage, $filters);
    }

    /**
     * Resolver contexto para el listado de salas de bingo automaticas.
     *
     * @param int $userId ID del usuario para filtrar
     * @return BingoRoomAutomaticSummaryDTO
     *
     * @throws DomainException Si no se puede resolver el contexto
     */
    private function summaryGameRooms(int $userId): BingoRoomAutomaticSummaryDTO
    {
        $commerceData = $this->resolveGameRooms($userId);

        $currencies = $this->currencyService->getAvailableCurrencies($userId, true);

        $currenciesWithActiveRate = ExchangeRate::active()
            ->whereIn('currency_id', collect($currencies)->pluck('id'))
            ->pluck('currency_id')
            ->unique()
            ->values()
            ->all();

        return new BingoRoomAutomaticSummaryDTO(
            rooms: $commerceData['rooms'],
            pagination: $commerceData['pagination'],
            currencies: $currencies,
            currenciesWithActiveRate: $currenciesWithActiveRate,
            bingoPatterns: $this->bingoPatterns->getByUserAndDefault($userId, ['id', 'name'], 'ACTIVE'),
            prizePoolTypes: $this->prizePoolTypes->getActiveTypes(),
            connectionTypes: $this->connectionTypes->getActiveConnectionTypes(),
            roomTypes: $this->roomTypes->getActiveTypes()
        );
    }
}
