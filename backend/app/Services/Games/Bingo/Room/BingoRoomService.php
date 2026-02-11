<?php

namespace App\Services\Games\Bingo\Room;

use App\DTOs\Games\Bingo\Room\BingoRoomSummaryDTO;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Pagination\LengthAwarePaginator;

final class BingoRoomService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Contracts\Games\Bingo\Room\BingoRoomRepositoryInterface $bingoRooms,
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencies,
        private readonly \App\Contracts\GameRepositoryInterface $games,
        private readonly \App\Contracts\Roles\Seller\SellerRepositoryInterface $sellerRepository,
        private readonly \App\Contracts\TimeZoneRepositoryInterface $timeZones
    ) {}

    /**
     * Resolver contexto para BingoRoom.
     *
     * @param string $context
     * @return mixed
     */
    public function context(string $context)
    {
        return match ($context) {
            'fetch' => $this->fetchGameRooms(),
            'summary' => $this->summaryGameRooms(),
            default => throw new DomainException('No existe el contexto: ' . $context),
        };
    }

    /**
     * Resolver listado de salas de bingo.
     *
     * @return array [gameRooms, pagination]
     */
    private function resolveGameRooms(): array
    {
        $labels  = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'Country.name', 'phone', 'User.email', 'status_badge'];

        $source = $this->bingoRooms->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['country:id,name', 'user:id,email'],
            conditions: [],
            fields: ['id', 'country_id', 'user_id', 'name', 'phone', 'status']
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

        return ['rooms' => $rooms, 'pagination' => $pagination];
    }

    /**
     * Obtener las salas de bingo.
     *
     * @return array
     */
    private function fetchGameRooms(): array
    {
        return $this->resolveGameRooms();
    }

    /**
     * Resolver contexto para el listado de salas de bingo.
     *
     * @return BingoRoomSummaryDTO
     */
    private function summaryGameRooms(): BingoRoomSummaryDTO
    {
        $commerceData = $this->resolveGameRooms();

        return new BingoRoomSummaryDTO(
            rooms: $commerceData['rooms'],
            pagination: $commerceData['pagination'],
            countries: $this->countries->getActive(['id', 'name']),
            currencies: $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
            games: $this->games->getActive(['id', 'name']),
            timeZones: $this->timeZones->getActive(['id', 'name', 'utc', 'country_id'])
        );
    }
}
