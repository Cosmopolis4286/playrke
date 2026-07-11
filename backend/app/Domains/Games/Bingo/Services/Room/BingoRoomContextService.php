<?php

namespace App\Domains\Games\Bingo\Services\Room;

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
        private readonly \App\Domains\Games\Bingo\Contracts\Room\BingoRoomRepositoryInterface $bingoRooms,
        private readonly \App\Services\Currency\CurrencyService $currencyService,
        private readonly \App\Contracts\GameRepositoryInterface $games,
        private readonly \App\Contracts\Roles\Seller\SellerRepositoryInterface $sellerRepository,
        private readonly \App\Contracts\TimeZoneRepositoryInterface $timeZones,
        private readonly \App\Domains\Games\Bingo\Contracts\Pattern\BingoPatternRepositoryInterface $bingoPatterns,
        private readonly \App\Contracts\Games\Common\PrizePool\PrizePoolTypeRepositoryInterface $prizePoolTypes,
        private readonly \App\Contracts\Games\Common\GameMode\GameModeRepositoryInterface $gameModes,
        private readonly \App\Contracts\Games\Common\Room\RoomTypeRepositoryInterface $roomTypes,
        private readonly \App\Contracts\UserRepositoryInterface $userRepository,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencyRepository,
        private readonly \App\Domains\Games\Bingo\Services\Card\Generate\BingoCardGenerateService $generateService,
        private readonly \App\Domains\Games\Bingo\Services\Config\BingoConfigContextService $bingoConfigContext,
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
        $labels  = ['Código', 'Estado', 'Tipo', 'Modo', 'Tipo Bote', 'Moneda(s)', 'Jugadores', 'Cartones', 'Premio'];
        $rowKeys = ['code_start_badge', 'status_badge', 'config.roomType.label', 'config.gameMode.label', 'config.prizePoolType.label', 'config.allowed_currencies_badge', 'total_players_badge', 'total_cards_badge', 'prize_pool_badge'];

        // Superadmin y admin ven todas las salas; commerce/customer solo las suyas
        $isGlobalAdmin = $this->userContext->userHasRole('superadmin')
            || $this->userContext->userHasRole('admin');

        $conditions = (!$isGlobalAdmin && $userId)
            ? ['creator_user_id' => $userId]
            : [];

        $source = $this->bingoRooms->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['config', 'config.roomType', 'config.gameMode', 'config.prizePoolType', 'status', 'rounds.prizes'],
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

            // Campos meta numéricos crudos (además de las versiones _badge con HTML de abajo):
            // el frontend los usa para lógica de negocio (hasPlayers, disable-edit, etc.),
            // no solo para mostrarlos — deben sobrevivir aunque las columnas visuales cambien.
            $row['total_players'] = (int) ($model?->total_players ?? 0);
            $row['total_cards']   = (int) ($model?->total_cards ?? 0);

            // Formatear columna Cartones: número vendido + cuota mínima apilados, en vez de
            // concatenados en un solo texto — la cuota se resalta en verde si ya se alcanzó.
            $cardsCount = $row['total_cards'];
            $minCards   = $config?->min_cards_to_start;
            $cardsCountClass = $cardsCount === 0 ? ' room-cards-cell__count--zero' : '';

            if ($minCards !== null && $minCards > 0) {
                $quotaMet = $cardsCount >= $minCards;
                $row['total_cards_badge'] = '<div class="room-cards-cell">'
                    . '<span class="room-cards-cell__count' . $cardsCountClass . '">' . $cardsCount . '</span>'
                    . '<span class="room-cards-cell__quota' . ($quotaMet ? ' room-cards-cell__quota--met' : '') . '">mín. ' . (int) $minCards . '</span>'
                    . '</div>';
            } else {
                $row['total_cards_badge'] = '<span class="room-cards-cell__count' . $cardsCountClass . '">' . $cardsCount . '</span>';
            }

            // Columna "Jugadores": atenuar el 0 para que no compita visualmente con salas con actividad real.
            $playersCount = $row['total_players'];
            $row['total_players_badge'] = '<span class="room-players-cell' . ($playersCount === 0 ? ' room-players-cell--zero' : '') . '">' . $playersCount . '</span>';

            // Columna "Código": código + fecha/hora de inicio (formato corto, sin segundos) apiladas.
            $startInfo = $model?->start_info;
            $startInfoShort = $startInfo && preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2})/', $startInfo, $m)
                ? "{$m[3]}/{$m[2]} {$m[4]}"
                : $startInfo;

            $row['code_start_badge'] = '<div class="room-code-cell">'
                . '<span class="room-code-cell__code">' . e($row['id'] ?? '') . '</span>'
                . '<span class="room-code-cell__date">' . e($startInfoShort ?? '') . '</span>'
                . '</div>';

            // Columna "Moneda(s)": pills discretas (metadata de contexto, no el dato principal
            // de la columna); campo separado con ids para filtrar.
            $currencyIds = (array) ($config?->allowed_currencies ?? []);
            $isoCodes = array_values(array_map(fn($id) => $currencyMap[$id] ?? $id, $currencyIds));

            $row['config_allowed_currencies_badge'] = '<div class="room-currency-pills">' . implode('', array_map(
                fn($iso) => '<span class="room-currency-pill">' . e($iso) . '</span>',
                $isoCodes
            )) . '</div>';
            $row['currency_ids'] = $currencyIds;

            // Columna "Premio": una línea por moneda permitida en la sala.
            // Premios FIJOS (bingo_round_prizes.currency_id) se usan tal cual; si el bote es
            // porcentual/acumulativo (sin currency_id), se convierte prize_pool (base USD)
            // a cada moneda con la tasa activa. Sin tasa activa -> "—" en esa línea.
            $fixedPrizesByCurrency = $model?->rounds
                ?->flatMap(fn($round) => $round->prizes)
                ->whereNotNull('currency_id')
                ->groupBy('currency_id')
                ->map(fn($prizes) => $prizes->sum('prize_value')) ?? collect();

            // OJO: 'prize_pool' no está en $rowKeys (solo se seleccionó en `fields` de la
            // query), por lo que buildRow() nunca lo copiaba al array $row — esto hacía
            // que el bote mostrado fuera siempre 0.00 sin importar el prize_pool real de
            // la sala. Se lee directamente del modelo, igual que total_players/total_cards.
            $prizePoolUsd = (float) ($model?->prize_pool ?? 0);

            $prizeLines = [];
            foreach ($currencyIds as $currencyId) {
                $iso = $currencyMap[$currencyId] ?? $currencyId;
                $currencyIdStr = (string) $currencyId;
                $amountClass = 'room-prize-cell__amount';
                $amountTitle = '';

                if ($fixedPrizesByCurrency->has($currencyIdStr)) {
                    $amount = (float) $fixedPrizesByCurrency->get($currencyIdStr);
                    $amountText = number_format($amount, 2);
                } else {
                    try {
                        $rate = $this->currencyExchangeRate->getActiveRate($currencyId);
                        $amount = $prizePoolUsd * $rate;
                        $amountText = number_format($amount, 2);
                    } catch (\App\Exceptions\Domain\ExchangeRate\ExchangeRateException) {
                        $amount = null;
                        $amountText = '—';
                        $amountClass .= ' room-prize-cell__amount--na';
                        $amountTitle = 'Sin tasa de cambio activa';
                    }
                }

                // Bote en 0 con cartones ya vendidos: señal a revisar (no un dato neutro),
                // se marca visualmente sin alterar la cifra real.
                if ($amount !== null && $amount <= 0.0 && $cardsCount > 0) {
                    $amountClass .= ' room-prize-cell__amount--zero';
                    $amountTitle = 'Bote en 0 con cartones vendidos — revisar';
                }

                $prizeLines[] = '<div class="room-prize-cell__line">'
                    . '<span class="room-prize-cell__currency">' . e($iso) . '</span>'
                    . '<span class="' . $amountClass . '"' . ($amountTitle !== '' ? ' title="' . e($amountTitle) . '"' : '') . '>' . $amountText . '</span>'
                    . '</div>';
            }

            $row['prize_pool_badge'] = '<div class="room-prize-cell">' . implode('', $prizeLines) . '</div>';
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
                    try {
                        $exchangeRate = $this->currencyExchangeRate->getActiveRate($playerCurrency->id);
                    } catch (\App\Exceptions\Domain\ExchangeRate\ExchangeRateException $e) {
                        // Vista de solo lectura (lobby): no bloquear la carga de la sala por
                        // falta de tasa — se deja constancia en logs para que se corrija la
                        // tasa antes de que el jugador intente comprar (ahí sí se bloquea).
                        \Illuminate\Support\Facades\Log::warning('[BingoRoomContextService] Sin tasa activa al armar contexto del lobby', [
                            'currency_id' => $playerCurrency->id,
                            'error'       => $e->getMessage(),
                        ]);
                    }
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
