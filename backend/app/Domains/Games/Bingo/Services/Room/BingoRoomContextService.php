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
     * @param int $page Página solicitada (1-based)
     * @param int $perPage Registros por página
     * @param array{
     *     status?: string,
     *     room_type_id?: string,
     *     game_mode_id?: string,
     *     prize_pool_type_id?: string,
     *     from_house?: string,
     *     currency_id?: string,
     * } $filters Filtros server-side sobre campos reales (ver BingoRoomRepository::paginateSummaryGameRooms)
     * @return array Listado de salas con paginación
     * @throws DomainException Si no se pueden resolver las salas de bingo
     */
    private function resolveSummaryGameRooms(?int $userId, int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $labels  = ['Código', 'Estado', 'Tipo', 'Modo', 'Tipo Bote', 'Moneda(s)', 'Jugadores', 'Cartones', 'Premio'];
        $rowKeys = ['code_start_badge', 'status_badge', 'config.roomType.label', 'config.gameMode.label', 'config.prizePoolType.label', 'config.allowed_currencies_badge', 'total_players_badge', 'total_cards_badge', 'prize_pool_badge'];

        // Superadmin y admin ven todas las salas; commerce/customer solo las suyas
        $isGlobalAdmin = $this->userContext->userHasRole('superadmin')
            || $this->userContext->userHasRole('admin');

        $conditions = (!$isGlobalAdmin && $userId)
            ? ['creator_user_id' => $userId]
            : [];

        $source = $this->bingoRooms->paginateSummaryGameRooms(
            perPage: $perPage,
            page: $page,
            conditions: $conditions,
            filters: $filters,
            // cardPrizes/cardLots solo traen las columnas necesarias para contar (vía
            // closure) — ganadores y cartones personalizados, sin cargar registros completos.
            relations: [
                'config', 'config.roomType', 'config.gameMode', 'config.prizePoolType', 'status', 'rounds.prizes',
                'cardPrizes' => fn($q) => $q->select('id', 'room_id', 'status')->where('status', 'ACTIVE'),
                'cardLots' => fn($q) => $q->select('id', 'room_id', 'card_count', 'has_custom_cards', 'status')->where('status', 'ACTIVE'),
            ],
            fields: ['id', 'uuid_code', 'status_id', 'creator_user_id', 'total_players', 'total_cards', 'prize_pool', 'metrics_by_currency', 'created_at', 'updated_at']
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

        $authUser  = \Illuminate\Support\Facades\Auth::user();
        $authUserId = $authUser ? (string) $authUser->id : null;
        $isAdmin    = $authUser && $authUser->hasRole(['admin', 'superadmin']);

        // Timezone del usuario que ve la tabla (no el del creador de cada sala) —
        // usado por getStatusBadgeAttribute() para mostrar "última actualización"
        // en hora local en vez de UTC crudo.
        $displayTimezone = $authUser?->timeZone?->name ?? 'UTC';

        // Último editor real de cada sala visible (tabla audits, evento 'updated'),
        // resuelto en UNA sola query agrupada por auditable_id — evita N+1 al
        // iterar 15 filas. Se setea en cada modelo ANTES de processCollectionTableData()
        // porque getStatusBadgeAttribute() (que arma el popover) corre dentro de ese
        // método y no puede recibir parámetros directamente.
        $lastEditorsByRoomId = $this->resolveLastEditorsForRooms($collection->pluck('id')->all());

        foreach ($collection as $model) {
            $model->displayTimezone = $displayTimezone;
            $model->lastEditor = $lastEditorsByRoomId[(string) $model->id] ?? null;
        }

        $rooms = $this->processCollectionTableData($collection, $labels, $rowKeys);

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

            // Quitar prefijo "Bote " del tipo de bote (ahorra espacio en la columna).
            // OJO: normalizeKey() convierte 'config.prizePoolType.label' a
            // 'config_prizepooltype_label' (minúsculas + guiones bajos) — buscar la key
            // con puntos literales aquí nunca coincidía y el prefijo nunca se quitaba.
            if (isset($row['config_prizepooltype_label'])) {
                $row['config_prizepooltype_label'] = preg_replace('/^Bote\s+/i', '', $row['config_prizepooltype_label']);
            }

            // Campos meta numéricos crudos (además de las versiones _badge con HTML de abajo):
            // el frontend los usa para lógica de negocio (hasPlayers, disable-edit, etc.),
            // no solo para mostrarlos — deben sobrevivir aunque las columnas visuales cambien.
            $row['total_players'] = (int) ($model?->total_players ?? 0);
            $row['total_cards']   = (int) ($model?->total_cards ?? 0);

            // Formatear columna Cartones: con mínimo configurado, barra de progreso hacia
            // la cuota (verde al alcanzarla); sin mínimo, solo el número vendido. Si hay
            // cartones personalizados (BingoCardLot.has_custom_cards), se anota aparte.
            $cardsCount = $row['total_cards'];
            $minCards   = $config?->min_cards_to_start;
            $cardsCountClass = $cardsCount === 0 ? ' room-cards-cell__count--zero' : '';

            $customCardsCount = (int) ($model?->cardLots
                ?->where('has_custom_cards', true)
                ->sum('card_count') ?? 0);

            $customCardsHtml = $customCardsCount > 0
                ? '<span class="room-cards-cell__custom" title="Cartones personalizados">'
                    . '<i class="fas fa-pen"></i>' . $customCardsCount
                    . '</span>'
                : '';

            if ($minCards !== null && $minCards > 0) {
                $pct = min(100, (int) round(($cardsCount / $minCards) * 100));
                $quotaMet = $cardsCount >= $minCards;
                $row['total_cards_badge'] = '<div class="room-cards-cell">'
                    . '<div class="room-cards-cell__row">'
                    . '<span class="room-cards-cell__count' . $cardsCountClass . '" title="Cartones vendidos"><i class="fas fa-clone"></i>' . $cardsCount . '</span>'
                    . '<span class="room-cards-cell__quota' . ($quotaMet ? ' room-cards-cell__quota--met' : '') . '">/ ' . (int) $minCards . '</span>'
                    . $customCardsHtml
                    . '</div>'
                    . '<div class="room-cards-cell__bar" title="' . $pct . '% de la cuota mínima">'
                    . '<div class="room-cards-cell__bar-fill' . ($quotaMet ? ' room-cards-cell__bar-fill--met' : '') . '" style="width:' . $pct . '%"></div>'
                    . '</div>'
                    . '</div>';
            } else {
                $row['total_cards_badge'] = '<div class="room-cards-cell"><div class="room-cards-cell__row">'
                    . '<span class="room-cards-cell__count' . $cardsCountClass . '" title="Cartones vendidos"><i class="fas fa-clone"></i>' . $cardsCount . '</span>'
                    . $customCardsHtml
                    . '</div></div>';
            }

            // Columna "Jugadores": jugadores + ganadores, con iconos para diferenciarlos
            // de un vistazo (persona vs. trofeo) — atenuados en 0 para no competir
            // visualmente con salas con actividad real. Si hay cupo configurado
            // (max_players), se anota "/N" y se marca en rojo al llenarse — mismo
            // criterio visual que la cuota mínima de cartones de arriba.
            $playersCount = $row['total_players'];
            $winnersCount = $model?->cardPrizes?->count() ?? 0;
            $maxPlayers   = $config?->max_players;
            $roomIsFull   = $maxPlayers !== null && $maxPlayers > 0 && $playersCount >= $maxPlayers;

            $playersQuotaHtml = ($maxPlayers !== null && $maxPlayers > 0)
                ? '<span class="room-players-cell__quota' . ($roomIsFull ? ' room-players-cell__quota--full' : '') . '">/ ' . (int) $maxPlayers . '</span>'
                : '';

            $row['total_players_badge'] = '<div class="room-players-cell">'
                . '<span class="room-players-cell__stat' . ($playersCount === 0 ? ' room-players-cell__stat--zero' : '') . ($roomIsFull ? ' room-players-cell__stat--full' : '') . '" title="Jugadores">'
                . '<i class="fas fa-user"></i>' . $playersCount
                . '</span>'
                . $playersQuotaHtml
                . '<span class="room-players-cell__stat' . ($winnersCount === 0 ? ' room-players-cell__stat--zero' : '') . '" title="Ganadores">'
                . '<i class="fas fa-trophy"></i>' . $winnersCount
                . '</span>'
                . '</div>';

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
            // de la columna) + badge con cartones vendidos en esa moneda (metrics_by_currency),
            // para comparar de un vistazo cuál moneda vendió más sin abrir "Detalles de Sala".
            $currencyIds = (array) ($config?->allowed_currencies ?? []);
            $metricsByCurrency = $model?->metrics_by_currency ?? [];

            $currencyPillsHtml = array_map(function ($currencyId) use ($currencyMap, $metricsByCurrency) {
                $iso = $currencyMap[$currencyId] ?? $currencyId;
                $cardsSold = (int) ($metricsByCurrency[(string) $currencyId]['total_cards'] ?? 0);

                return '<span class="room-currency-pill">'
                    . e($iso)
                    . '<span class="room-currency-pill__count" title="Cartones vendidos en ' . e($iso) . '">' . $cardsSold . '</span>'
                    . '</span>';
            }, $currencyIds);

            $row['config_allowed_currencies_badge'] = '<div class="room-currency-pills">' . implode('', $currencyPillsHtml) . '</div>';
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
            $totalUsdApprox = 0.0;
            $hasUnconvertedLine = false;

            foreach ($currencyIds as $currencyId) {
                $iso = $currencyMap[$currencyId] ?? $currencyId;
                $currencyIdStr = (string) $currencyId;
                $amountClass = 'room-prize-cell__amount';
                $amountTitle = '';
                $amountUsd = null;

                if ($fixedPrizesByCurrency->has($currencyIdStr)) {
                    $amount = (float) $fixedPrizesByCurrency->get($currencyIdStr);
                    $amountText = number_format($amount, 2);

                    // Premio FIJO: está en moneda local, se necesita la tasa para
                    // aportar su equivalente en USD al total aproximado.
                    try {
                        $rate = $this->currencyExchangeRate->getActiveRate($currencyId);
                        $amountUsd = $rate > 0 ? $amount / $rate : null;
                    } catch (\App\Exceptions\Domain\ExchangeRate\ExchangeRateException) {
                        $amountUsd = null;
                    }
                } else {
                    try {
                        $rate = $this->currencyExchangeRate->getActiveRate($currencyId);
                        $amount = $prizePoolUsd * $rate;
                        $amountText = number_format($amount, 2);
                        $amountUsd = $prizePoolUsd;
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

                if ($amountUsd !== null) {
                    $totalUsdApprox += $amountUsd;
                } else {
                    $hasUnconvertedLine = true;
                }

                $prizeLines[] = '<div class="room-prize-cell__line">'
                    . '<span class="room-prize-cell__currency">' . e($iso) . '</span>'
                    . '<span class="' . $amountClass . '"' . ($amountTitle !== '' ? ' title="' . e($amountTitle) . '"' : '') . '>' . $amountText . '</span>'
                    . '</div>';
            }

            // Total aproximado en USD: solo aporta cuando hay 2+ monedas (con 1 sola,
            // la línea ya es el total). Se marca "≈" y con "*" si alguna línea no pudo
            // convertirse (sin tasa activa), para no aparentar precisión que no existe.
            $totalLineHtml = '';
            if (count($currencyIds) > 1) {
                $totalTitle = $hasUnconvertedLine
                    ? 'Aproximado: no incluye monedas sin tasa de cambio activa'
                    : 'Total aproximado en USD';
                $totalLineHtml = '<div class="room-prize-cell__total" title="' . e($totalTitle) . '">'
                    . '≈ $' . number_format($totalUsdApprox, 2) . ' USD' . ($hasUnconvertedLine ? '*' : '')
                    . '</div>';
            }

            $row['prize_pool_badge'] = '<div class="room-prize-cell">' . implode('', $prizeLines) . $totalLineHtml . '</div>';
        }
        unset($row);

        return ['rooms' => $rooms, 'pagination' => $pagination];
    }

    /**
     * Resuelve, en una sola query, el último editor real (tabla audits, evento
     * 'updated' con user_id no nulo) de cada sala en $roomIds — usado para el
     * popover de la columna "Estado" sin incurrir en N+1 por fila.
     *
     * @param array<int, string|int> $roomIds
     * @return array<string, array{username: string|null, at: \Illuminate\Support\Carbon}>
     */
    private function resolveLastEditorsForRooms(array $roomIds): array
    {
        if (empty($roomIds)) {
            return [];
        }

        $roomIdStrings = array_map(fn($id) => (string) $id, $roomIds);

        $audits = \App\Models\Audit::query()
            ->where('auditable_type', \App\Domains\Games\Bingo\Models\Room\BingoRoom::class)
            ->where('event', 'updated')
            ->whereNotNull('user_id')
            ->whereRaw('JSON_UNQUOTE(auditable_id) IN (' . implode(',', array_fill(0, count($roomIdStrings), '?')) . ')', $roomIdStrings)
            ->with('user:id,username')
            ->orderByDesc('id')
            ->get(['id', 'auditable_id', 'user_id', 'created_at']);

        $result = [];
        foreach ($audits as $audit) {
            $roomId = (string) $audit->auditable_id;
            // Ya viene ordenado desc por id — el primero visto por roomId es el más reciente.
            if (isset($result[$roomId])) {
                continue;
            }
            $result[$roomId] = [
                'username' => $audit->user?->username,
                'at'       => $audit->created_at,
            ];
        }

        return $result;
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
     * Refetch server-side del listado de salas (paginación/filtros/búsqueda
     * de Room/Summary tras la carga inicial vía Inertia) — a diferencia de
     * summaryGameRooms(), no reconstruye catálogos (monedas, patrones, tipos)
     * que no cambian entre páginas, solo `rooms` + `pagination`.
     *
     * @param array{
     *     status?: string,
     *     room_type_id?: string,
     *     game_mode_id?: string,
     *     prize_pool_type_id?: string,
     *     from_house?: string,
     *     currency_id?: string,
     * } $filters
     */
    public function summaryDataForRefetch(int $userId, int $page, int $perPage, array $filters): array
    {
        return $this->resolveSummaryGameRooms($userId, $page, $perPage, $filters);
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
                    ->map(fn($ru) => ['card_count' => $ru->cards_purchased])
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

            $roomIds = $rooms->pluck('id')->map(fn ($id) => (string) $id)->all();

            return $this->generateService->getPlayerCardPricesForRooms($roomIds, $userId);
        } catch (\Throwable) {
            return null;
        }
    }
}
