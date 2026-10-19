<?php

namespace App\Domains\Games\Bingo\Services\Room;

use App\Domains\Games\Bingo\DTOs\Room\BingoGameRoomsDTO;
use App\Domains\Games\Bingo\DTOs\Room\BingoRoomSummaryDTO;
use App\Models\ExchangeRate;
use App\Models\Game;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

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
        private readonly \App\Contracts\Games\Common\Jackpot\JackpotTypeRepositoryInterface $jackpotTypes,
        private readonly \App\Contracts\Games\Common\ConnectionType\ConnectionTypeRepositoryInterface $connectionTypes,
        private readonly \App\Contracts\Games\Common\Room\RoomTypeRepositoryInterface $roomTypes,
        private readonly \App\Contracts\Games\Common\GameMode\GameModeRepositoryInterface $gameModeCatalog,
        private readonly \App\Contracts\UserRepositoryInterface $userRepository,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencyRepository,
        private readonly \App\Domains\Games\Bingo\Services\Card\Generate\BingoCardGenerateService $generateService,
        private readonly \App\Domains\Games\Bingo\Services\Config\BingoConfigContextService $bingoConfigContext,
        private readonly \App\Services\ExchangeRate\CurrencyExchangeRateService $currencyExchangeRate,
        private readonly \App\Services\Games\UserTimezoneService $userTimezone,
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
     *     connection_type_id?: string,
     *     prize_pool_type_id?: string,
     *     from_house?: string,
     *     currency_id?: string,
     *     date_from?: string,
     *     date_to?: string,
     * } $filters Filtros server-side sobre campos reales (ver BingoRoomRepository::paginateSummaryGameRooms)
     * @return array Listado de salas con paginación
     * @throws DomainException Si no se pueden resolver las salas de bingo
     */
    private function resolveSummaryGameRooms(?int $userId, int $page = 1, int $perPage = 15, array $filters = []): array
    {
        // Superadmin y admin ven todas las salas; commerce/customer solo las suyas
        $isGlobalAdmin = $this->userContext->userHasRole('superadmin')
            || $this->userContext->userHasRole('admin');

        // Columna "Comercializadora" solo aporta valor a quien ve MÁS de una
        // (admin/superadmin) — un commerce/customer viendo solo sus propias
        // salas ya sabe de quién son todas, agregarla sería ruido redundante.
        $labels  = ['Código', 'Estado', 'Tipo', 'Modo', 'Tipo Bote', 'Moneda(s)', 'Jugadores', 'Cartones', 'Premio'];
        $rowKeys = ['code_start_badge', 'status_badge', 'config.roomType.label', 'config.connectionType.label', 'config.prizePoolType.label', 'config.allowed_currencies_badge', 'total_players_badge', 'total_cards_badge', 'prize_pool_badge'];

        if ($isGlobalAdmin) {
            array_splice($labels, 1, 0, ['Comercializadora']);
            array_splice($rowKeys, 1, 0, ['commerce_badge']);
        }

        $conditions = $this->resolveVisibilityConditions($userId, $isGlobalAdmin, $filters);
        unset($filters['commerce_id']);

        $source = $this->bingoRooms->paginateSummaryGameRooms(
            perPage: $perPage,
            page: $page,
            conditions: $conditions,
            filters: $filters,
            // cardPrizes/cardLots solo traen las columnas necesarias para contar (vía
            // closure) — ganadores y cartones personalizados, sin cargar registros completos.
            relations: [
                'config', 'config.roomType', 'config.connectionType', 'config.prizePoolType', 'status', 'rounds.prizes',
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

        // Solo se resuelve para admin/superadmin — commerce/customer ya ven
        // únicamente sus propias salas (condiciones arriba), no necesitan esta
        // columna. Evita el costo de resolución para el caso común (no-admin).
        $commercesByCreator = $isGlobalAdmin
            ? $this->resolveCommercesForCreators($collection->pluck('creator_user_id')->unique()->filter()->all())
            : [];

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

            // Lista de invitados: solo salas VIP, gestionada por su creador o admin.
            $row['room_type_code']         = $config?->roomType?->code;
            $row['can_manage_invitations'] = $row['room_type_code'] === 'VIP' && ($isCreator || $isAdmin)
                && !in_array($row['status'], ['FINISHED', 'CANCELLED'], true);

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

            // Columna "Comercializadora" — solo admin/superadmin (ver $isGlobalAdmin
            // arriba). Antes esta pantalla no distinguía de qué comercio era cada
            // sala; un admin viendo todas las salas del sistema no tenía forma de
            // saberlo sin abrir el detalle. Una sala puede pertenecer a un Commerce
            // real, o haber sido creada directo por un admin/superadmin (sala de
            // sistema, sin comercio asociado — caso legítimo, se distingue con su
            // propio badge en vez de mostrarla como "desconocida").
            if ($isGlobalAdmin) {
                $creatorId = (string) ($model?->creator_user_id ?? '');
                $owner = $commercesByCreator[$creatorId] ?? null;

                $row['commerce_badge'] = match ($owner['type'] ?? null) {
                    'commerce' => '<span class="room-commerce-pill">' . e($owner['name']) . '</span>',
                    'admin'    => '<span class="room-commerce-pill room-commerce-pill--admin">Admin: ' . e($owner['name']) . '</span>',
                    default    => '<span class="room-commerce-pill room-commerce-pill--unknown">—</span>',
                };
                // Campo meta crudo (no HTML) — usado por el filtro "Comercializadora"
                // client-side para la opción especial "Salas de sistema (admin)".
                $row['creator_owner_type'] = $owner['type'] ?? 'unknown';
            }
        }
        unset($row);

        return ['rooms' => $rooms, 'pagination' => $pagination];
    }

    /**
     * Condiciones de visibilidad por rol, compartidas entre el listado
     * paginado (resolveSummaryGameRooms) y el export CSV (exportQuery): un
     * admin/superadmin ve todas las salas; commerce/customer/subsidiary solo
     * las suyas, salvo que el filtro "Comercializadora" acote a un árbol de
     * usuarios (Commerce + sus Sellers) o a las salas de sistema (admin).
     *
     * @return array<string, mixed>
     */
    private function resolveVisibilityConditions(?int $userId, bool $isGlobalAdmin, array $filters): array
    {
        $conditions = (!$isGlobalAdmin && $userId)
            ? ['creator_user_id' => $userId]
            : [];

        if ($isGlobalAdmin && !empty($filters['commerce_id'])) {
            // '__SYSTEM__' es el valor centinela de la opción "Salas de sistema
            // (admin)" del filtro — ver commerceOptions en Summary.vue. No es un
            // id de Commerce real, así que se resuelve aparte, contra usuarios
            // admin/superadmin en vez de contra la jerarquía comercial.
            $conditions['creator_user_id_in'] = $filters['commerce_id'] === '__SYSTEM__'
                ? $this->adminUserIds()
                : $this->sellerUserIdsForCommerce($filters['commerce_id']);
        }

        return $conditions;
    }

    /**
     * Conteo por estado de las salas que ve el usuario (mismo criterio de
     * visibilidad que el listado) + cuántas activas inician hoy — KPIs de
     * la cabecera de Room/Summary.
     *
     * @return array{total: int, active: int, today: int, in_progress: int, finished: int, cancelled: int, inactive: int}
     */
    public function statusCounts(?int $userId): array
    {
        $base = $this->exportQuery($userId, [])->setEagerLoads([])->reorder();

        $byStatus = (clone $base)
            ->join('game_status as gs_count', 'gs_count.id', '=', 'bingo_rooms.status_id')
            ->groupBy('gs_count.code')
            ->selectRaw('gs_count.code AS code, COUNT(*) AS total')
            ->toBase()
            ->pluck('total', 'code')
            ->map(fn ($n) => (int) $n);

        $today = (clone $base)
            ->whereHas('status', fn ($q) => $q->where('code', 'ACTIVE'))
            ->whereHas('config', fn ($q) => $q->whereDate('date', now()->toDateString()))
            ->count();

        return [
            'total'       => (int) $byStatus->sum(),
            'active'      => $byStatus->get('ACTIVE', 0),
            'today'       => $today,
            'in_progress' => $byStatus->get('IN_PROGRESS', 0),
            'finished'    => $byStatus->get('FINISHED', 0),
            'cancelled'   => $byStatus->get('CANCELLED', 0) + $byStatus->get('REFUND', 0),
            'inactive'    => $byStatus->get('INACTIVE', 0),
        ];
    }

    /**
     * Query (sin paginar) del mismo conjunto de salas que ve el listado
     * Room/Summary bajo los mismos filtros — usado por BingoRoomController::
     * export() para el CSV completo, no solo la página visible. Mismo
     * criterio de visibilidad por rol que resolveSummaryGameRooms().
     *
     * @param array{
     *     status?: string, room_type_id?: string, connection_type_id?: string,
     *     prize_pool_type_id?: string, from_house?: string, currency_id?: string,
     *     commerce_id?: string, date_from?: string, date_to?: string,
     * } $filters
     */
    public function exportQuery(?int $userId, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $isGlobalAdmin = $this->userContext->userHasRole('superadmin')
            || $this->userContext->userHasRole('admin');

        $conditions = $this->resolveVisibilityConditions($userId, $isGlobalAdmin, $filters);
        unset($filters['commerce_id']);

        return $this->bingoRooms->queryForExport($conditions, $filters);
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
     * Resuelve, para un conjunto de user_id creadores de sala, a qué Commerce
     * pertenece cada uno — sin equivalente previo a Seller::resolveCommerce()
     * para "dueño de esta sala" (el creador puede ser un Commerce directo, un
     * Seller/Subsidiary vendiendo a nombre de un Commerce, o un admin/superadmin
     * creando una sala de sistema sin comercio asociado).
     *
     * Resuelve en 3 queries batch (Commerce directo, Seller, admin/superadmin
     * para los user_id restantes) en vez de N+1 por fila — el volumen esperado
     * es bajo (creadores únicos de una página de 15-20 salas, casi siempre
     * repetidos entre filas).
     *
     * @param array<int, string> $creatorUserIds
     * @return array<string, array{type: 'commerce'|'admin', name: string}> indexado por creator_user_id
     */
    private function resolveCommercesForCreators(array $creatorUserIds): array
    {
        if (empty($creatorUserIds)) {
            return [];
        }

        $result = [];

        // 1. Creador es un Commerce directo.
        $commercesByUserId = \App\Models\Roles\Commerce::whereIn('user_id', $creatorUserIds)
            ->get(['user_id', 'name'])
            ->keyBy(fn ($c) => (string) $c->user_id);

        foreach ($commercesByUserId as $userId => $commerce) {
            $result[$userId] = ['type' => 'commerce', 'name' => $commerce->name];
        }

        // 2. Creadores restantes: puede ser un Seller (directo o vía Subsidiary).
        $remainingUserIds = array_diff($creatorUserIds, array_keys($result));

        if (!empty($remainingUserIds)) {
            $sellers = \App\Models\Roles\Seller::whereIn('user_id', $remainingUserIds)
                ->with(['subsidiary.commerce:id,name', 'managerCommerce:id,user_id,name'])
                ->get(['id', 'user_id', 'manager_user_id', 'subsidiary_id']);

            foreach ($sellers as $seller) {
                $commerce = $seller->resolveCommerce();
                if ($commerce) {
                    $result[(string) $seller->user_id] = ['type' => 'commerce', 'name' => $commerce->name];
                }
            }
        }

        // 3. Creadores restantes: sala de sistema, creada directo por un
        // admin/superadmin (sin comercio asociado — caso legítimo, no huérfano).
        $remainingUserIds = array_diff($creatorUserIds, array_keys($result));

        if (!empty($remainingUserIds)) {
            $admins = \App\Models\User::whereIn('id', $remainingUserIds)
                ->whereHas('roles', fn ($q) => $q->whereIn('prefix', ['admin', 'superadmin']))
                ->get(['id', 'username']);

            foreach ($admins as $admin) {
                $result[(string) $admin->id] = ['type' => 'admin', 'name' => $admin->username];
            }
        }

        return $result;
    }

    /**
     * user_id "creadores posibles" bajo un Commerce dado — el propio Commerce
     * más todos sus Sellers (directos y vía cualquiera de sus Subsidiaries).
     * Usado por el filtro "Comercializadora" (admin/superadmin): una sala
     * puede haber sido creada por el Commerce mismo o por uno de sus
     * vendedores, ver resolveSummaryGameRooms().
     *
     * @return array<int, string>
     */
    private function sellerUserIdsForCommerce(string $commerceId): array
    {
        $commerce = \App\Models\Roles\Commerce::find($commerceId, ['id', 'user_id']);

        if (!$commerce) {
            return ['0']; // id inexistente: fuerza cero resultados, mismo criterio que ExternalPlayerAdminService.
        }

        $subsidiaryIds = \App\Models\Roles\Subsidiary::where('commerce_id', $commerce->id)->pluck('id');

        $sellerUserIds = \App\Models\Roles\Seller::query()
            ->where('manager_user_id', $commerce->user_id)
            ->orWhereIn('subsidiary_id', $subsidiaryIds)
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        return [...$sellerUserIds, (string) $commerce->user_id];
    }

    /**
     * user_id de todos los usuarios admin/superadmin — usado por el filtro
     * "Comercializadora" (opción "Salas de sistema (admin)", ver
     * resolveSummaryGameRooms()) para aislar salas creadas directo por un
     * admin, sin comercio asociado.
     *
     * @return array<int, string>
     */
    private function adminUserIds(): array
    {
        return \App\Models\User::whereHas('roles', fn ($q) => $q->whereIn('prefix', ['admin', 'superadmin']))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
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

        $isGlobalAdmin = $this->userContext->userHasRole('superadmin')
            || $this->userContext->userHasRole('admin');

        return new BingoRoomSummaryDTO(
            rooms: $commerceData['rooms'],
            pagination: $commerceData['pagination'],
            currencies: $currencies,
            currenciesWithActiveRate: $currenciesWithActiveRate,
            bingoPatterns: $this->bingoPatterns->getByUserAndDefault($userId, ['id', 'name'], 'ACTIVE'),
            prizePoolTypes: $this->prizePoolTypes->getActiveTypes(),
            jackpotTypes: $this->jackpotTypes->getActiveTypes(),
            connectionTypes: $this->connectionTypes->getActiveConnectionTypes(),
            roomTypes: $this->roomTypes->getActiveTypes(),
            gameModeCatalog: $this->bingoGameId() ? $this->gameModeCatalog->getActiveByGame((string) $this->bingoGameId()) : collect(),
            commerces: $isGlobalAdmin
                ? \App\Models\Roles\Commerce::where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name'])->toArray()
                : [],
            statusCounts: $this->statusCounts($userId),
        );
    }

    /**
     * ID del juego BINGO, cacheado indefinidamente — misma cache key que
     * BingoConfigContextService::bingoGameId() (fila de catálogo estática).
     */
    private function bingoGameId(): int|string|null
    {
        return Cache::rememberForever('game:id:BINGO', fn () => Game::where('name', 'BINGO')->value('id'));
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
     *     connection_type_id?: string,
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
        // "Hoy" en el timezone del propio usuario que consulta (jugador/admin/commerce),
        // no el timezone del servidor — las columnas `date` de cada sala están guardadas
        // en el timezone de SU creador, así que comparar contra un "hoy" de servidor
        // (normalmente UTC) desalinea la ventana cuando el usuario y el servidor no
        // comparten timezone. Ver App\Services\Games\UserTimezoneService.
        $today = now($this->userTimezone->resolve($userId))->toDateString();

        // Verificar roles del usuario
        $isPlayer          = $this->userContext->userHasRole('player');
        $isCommerceCustomer = $this->userContext->userHasRole('commerce') || $this->userContext->userHasRole('customer');
        $isAdmin           = $this->userContext->userHasRole('admin') || $this->userContext->userHasRole('superadmin');

        // Usar el nuevo método con filtrado por rol
        $rooms = $this->bingoRooms->getActiveRoomsForViewWithRoleFilter($userId, $today, $isPlayer, $isCommerceCustomer, $isAdmin, $this->resolveSellerCommerceUserId($userId));

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
        ['rate' => $exchangeRate, 'sym' => $currencySym] = $this->resolvePlayerCurrency($userId, $isPlayer);

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
     * user_ids de los dueños de las salas que vende el vendedor autenticado
     * (su Commerce real vía Seller::resolveCommerce() y, si es de agencia, la
     * agencia), o null si no es seller o no tiene comercio resoluble.
     */
    private function resolveSellerCommerceUserId(int $userId): ?array
    {
        if (!$this->userContext->userHasRole('seller')) {
            return null;
        }

        // Comercializadora + agencia del vendedor (Seller::roomOwnerUserIds()):
        // una agencia con permiso individual games.bingo.manage puede crear
        // salas, y sus vendedores deben verlas igual que las de su comercio.
        $owners = \App\Models\Roles\Seller::where('user_id', $userId)
            ->first(['id', 'manager_user_id', 'subsidiary_id'])
            ?->roomOwnerUserIds() ?? [];

        return $owners !== [] ? $owners : null;
    }

    /**
     * Resuelve moneda/tasa de cambio para el contexto de salas de juego —
     * extraído de viewGameRooms() para ser reutilizado también por
     * gameRoomsDataForRefetch() (endpoint de filtros). Comercio/seller/admin
     * siempre ven en USD (manejan múltiples monedas); solo players usan la
     * moneda fija de su país.
     *
     * @return array{rate: float, sym: string}
     */
    private function resolvePlayerCurrency(int $userId, bool $isPlayer): array
    {
        $exchangeRate = 1.0;
        $currencySym = 'USD';

        if (!$isPlayer) {
            return ['rate' => $exchangeRate, 'sym' => $currencySym];
        }

        $currentUserId = $this->userContext->resolveUserId();
        $countryId = $this->userRepository->getValueById($currentUserId, 'country_id');

        if (!$countryId) {
            return ['rate' => $exchangeRate, 'sym' => $currencySym];
        }

        $playerCurrency = $this->currencyRepository
            ->getByCountry($countryId, ['id', 'iso_code', 'symbol'])
            ->first();

        if (!$playerCurrency) {
            return ['rate' => $exchangeRate, 'sym' => $currencySym];
        }

        $currencySym = $playerCurrency->symbol;
        try {
            $exchangeRate = $this->currencyExchangeRate->getActiveRate($playerCurrency->id);
        } catch (\App\Exceptions\Domain\ExchangeRate\ExchangeRateException $e) {
            // Vista de solo lectura (lobby/listado): no bloquear la carga de la sala por
            // falta de tasa — se deja constancia en logs para que se corrija la
            // tasa antes de que el jugador intente comprar (ahí sí se bloquea).
            \Illuminate\Support\Facades\Log::warning('[BingoRoomContextService] Sin tasa activa al armar contexto del lobby', [
                'currency_id' => $playerCurrency->id,
                'error'       => $e->getMessage(),
            ]);
        }

        return ['rate' => $exchangeRate, 'sym' => $currencySym];
    }

    /**
     * Refetch paginado/filtrado del listado de salas de juego (game-rooms),
     * usado por el botón de filtros (fecha/variante) del listado de jugador —
     * mismo patrón que summaryDataForRefetch() (listado admin/commerce), pero
     * para BingoGameRoomsDTO en vez de BingoRoomSummaryDTO.
     *
     * @param array{date_from?: string, date_to?: string, variant?: string} $filters
     * @return array{rooms: array, pagination: array, kpis: array}
     */
    public function gameRoomsDataForRefetch(int $userId, int $page, int $perPage, array $filters): array
    {
        $isPlayer           = $this->userContext->userHasRole('player');
        $isCommerceCustomer = $this->userContext->userHasRole('commerce') || $this->userContext->userHasRole('customer');
        $isAdmin            = $this->userContext->userHasRole('admin') || $this->userContext->userHasRole('superadmin');
        $sellerCommerceUserId = $this->resolveSellerCommerceUserId($userId);

        // Sin filtro de fecha explícito del usuario: completar el default con "hoy" en
        // SU timezone (no el del servidor) antes de llegar al repositorio — mismo
        // criterio que viewGameRooms(), ver comentario ahí.
        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $filters['date_from'] = now($this->userTimezone->resolve($userId))->toDateString();
        }

        $paginator = $this->bingoRooms->paginateRoomsForViewWithFilters(
            $userId,
            $filters,
            $page,
            $perPage,
            $isPlayer,
            $isCommerceCustomer,
            $isAdmin,
            $sellerCommerceUserId
        );

        ['rate' => $exchangeRate, 'sym' => $currencySym] = $this->resolvePlayerCurrency($userId, $isPlayer);

        $roomIds = collect($paginator->items())->pluck('id')->map(fn ($id) => (string) $id)->all();
        $cardPrices = $isPlayer && !empty($roomIds)
            ? $this->generateService->getPlayerCardPricesForRooms($roomIds, $userId)
            : null;

        $dto = new BingoGameRoomsDTO(
            rooms: collect($paginator->items()),
            exchangeRate: $exchangeRate,
            currencySym: $currencySym,
            userToken: null,
            dateTimeRooms: null,
            cardPrices: $cardPrices,
            bingoRules: '',
            imageBingoRules: '',
        );

        // El total de salas activas para el KPI es el mismo total ya resuelto
        // por el paginator (paginate() ya ejecuta su propio COUNT(*) internamente)
        // — evita una segunda query de conteo redundante en cada refetch.
        $nextRoom = $this->bingoRooms->findNextStartingRoom($userId, $filters, $isPlayer, $isCommerceCustomer, $isAdmin, $sellerCommerceUserId);

        return [
            'rooms' => $dto->toView()['rooms'],
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'kpis' => [
                'next_room_unix_date' => $nextRoom?->config?->unix_date,
                'next_room_id' => $nextRoom?->getRouteKey(),
                'total_active_rooms' => $paginator->total(),
            ],
        ];
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
            $today = now($this->userTimezone->resolve($userId))->toDateString();
            $rooms = $this->bingoRooms->getActiveRoomsForViewWithRoleFilter($userId, $today, true, $isCommerceCustomer);

            if ($rooms->isEmpty()) {
                return null;
            }

            $roomIds = $rooms->pluck('id')->map(fn ($id) => (string) $id)->all();

            return $this->generateService->getPlayerCardPricesForRooms($roomIds, $userId);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Vitrina pública de salas (sin sesión, ver PublicController::gameRoomsShowcase())
     * — mismo DTO que el lobby autenticado (misma barra de venta/pozo, mismo
     * preview de jugadores), pero sin cardPrices (no hay moneda de jugador
     * que resolver sin cuenta) y sin players_room (no hay "mis cartones" para
     * un visitante anónimo). El botón de compra en esta vista lleva a
     * login/registro — ver decisión de no abrir compra sin cuenta.
     */
    public function publicShowcase(): BingoGameRoomsDTO
    {
        $rooms = $this->bingoRooms->getActivePublicRoomsForShowcase();

        $timestamp = now()->valueOf();

        $dateTimeRooms = [
            'timestamp' => $timestamp,
            'rooms'     => $rooms->map(fn ($room) => [
                'id'           => (string) $room->id,
                'config'       => [
                    'unix_date' => $room->config?->unix_date,
                    'room_type' => $room->config?->roomType?->code ?? 'PUBLIC',
                ],
                'players_room' => null,
            ])->values()->all(),
        ];

        // bingoRules/imageBingoRules son configurables por creador de sala
        // (getBingoRules(int $userId)) — sin sesión no hay un "dueño" único
        // que resolver cuando la vitrina mezcla salas de varios creadores,
        // así que se omiten aquí (el reglamento completo no es crítico para
        // una vista que solo invita a registrarse, no a jugar).
        return new BingoGameRoomsDTO(
            rooms: $rooms,
            exchangeRate: 1.0,
            currencySym: 'USD',
            userToken: null,
            dateTimeRooms: $dateTimeRooms,
            cardPrices: null,
            bingoRules: '',
            imageBingoRules: '',
        );
    }

    /**
     * Top 10 compradores de una sala por cantidad de cartones ("Top de
     * Compras" del lobby de salas) — bajo demanda, no se carga junto con el
     * listado completo de salas para no agregar una query pesada por sala.
     *
     * @return array<int, array{user_id: string, name: string, avatar_url: ?string, cards_count: int}>
     */
    public function topBuyers(string $roomId): array
    {
        return $this->bingoRooms->getTopBuyers($roomId)
            ->map(fn (array $row) => [
                'user_id'     => $row['user_id'],
                'name'        => $row['first_name'] ?: ($row['username'] ?? 'Jugador'),
                'avatar_url'  => $row['avatar_url'],
                'cards_count' => $row['cards_count'],
            ])
            ->values()
            ->all();
    }
}
