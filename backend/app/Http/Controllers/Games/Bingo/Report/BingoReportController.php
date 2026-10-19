<?php

namespace App\Http\Controllers\Games\Bingo\Report;

use App\Http\Controllers\Controller;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Currency;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use App\Services\Roles\RoleUserService;
use App\Traits\StreamsCsvExport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador de administración del juego de Bingo.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class BingoReportController extends Controller
{
    use StreamsCsvExport;

    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly RoleUserService $roleUserService,
    ) {
        $this->authorizeResource(BingoRoom::class, 'room');
    }

    /** Rol activo del usuario, resuelto una sola vez por request. */
    private ?string $activeRole = null;

    /** @var array{owner: string, network: string[]}|null|false false = aún no resuelto */
    private array|null|false $ownerScope = false;

    private function activeRole(): string
    {
        return $this->activeRole ??= (string) $this->roleUserService->getActiveUserRolePrefix((int) Auth::id());
    }

    /**
     * El vendedor ve SOLO sus propias ventas (seller_user_id = su user_id),
     * no el scope de dueño de sala: le mostraría las ventas de TODOS los
     * vendedores de su comercio (con nombres de jugadores y comisiones de
     * sala/plataforma). Mismo criterio que ya usa su dashboard
     * (SellerDashboardContextService). seller_user_id se escribe en
     * bingo_card_lots/bingo_cards al vender (BingoCardGenerationService) y
     * se copia a bingo_card_prizes al premiar (BingoDrawService).
     */
    private function isSellerView(): bool
    {
        return $this->activeRole() === 'seller';
    }

    /**
     * Scope de los roles "dueños" (commerce/subsidiary/customer), o null
     * para admin/superadmin (sin restricción). El vendedor no pasa por aquí
     * (ver isSellerView()).
     *
     * - owner:   su user_id — creator_user_id de las salas que le pertenecen.
     * - network: user_ids cuyas ventas son "suyas" en CUALQUIER sala
     *            (seller_user_id): el propio usuario + su red de vendedores,
     *            directos y de sus agencias. Misma definición que ya usan el
     *            dashboard y el Resumen Financiero (Commerce::networkUserIds(),
     *            Subsidiary::networkUserIds()).
     *
     * Antes el scope era BingoRoom::scopeAccessibleBy() — "mis salas + TODAS
     * las PUBLIC" —, lo que exponía a cualquier comercio/agencia las ventas,
     * jugadores, ganadores y comisiones de las salas públicas de OTROS
     * comercios. accessibleBy() es correcto para decidir en qué salas se
     * puede JUGAR/VENDER, no para decidir qué datos de negocio se pueden ver.
     *
     * @return array{owner: string, network: string[]}|null
     */
    private function ownerScope(): ?array
    {
        if ($this->ownerScope !== false) {
            return $this->ownerScope;
        }

        $userId = (string) Auth::id();

        return $this->ownerScope = match ($this->activeRole()) {
            'superadmin', 'admin' => null,
            'commerce' => [
                'owner'   => $userId,
                'network' => Commerce::where('user_id', $userId)->first()?->networkUserIds() ?? [$userId],
            ],
            'subsidiary' => [
                'owner'   => $userId,
                'network' => Subsidiary::where('user_id', $userId)->first()?->networkUserIds() ?? [$userId],
            ],
            // customer y cualquier otro rol con acceso: solo lo propio.
            default => ['owner' => $userId, 'network' => [$userId]],
        };
    }

    /**
     * Scope para queries sobre filas de VENTA (BingoCard, BingoCardPrize —
     * ambas con room_id y seller_user_id):
     * - vendedor: solo las que vendió él;
     * - dueño: todo lo ocurrido en SUS salas + lo vendido por SU red en
     *   cualquier otra sala (p. ej. sus vendedores en una sala pública);
     * - admin: sin filtro.
     */
    private function applySaleScope(Builder $query): Builder
    {
        $model = $query->getModel();

        if ($this->isSellerView()) {
            return $query->where($model->qualifyColumn('seller_user_id'), (string) Auth::id());
        }

        $scope = $this->ownerScope();

        if ($scope === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($model, $scope) {
            $q->whereIn($model->qualifyColumn('room_id'), BingoRoom::query()->select('id')->where('creator_user_id', $scope['owner']))
                ->orWhereIn($model->qualifyColumn('seller_user_id'), $scope['network']);
        });
    }

    /**
     * Scope para queries sobre BingoRoom en los reportes a nivel SALA
     * (Reporte de Ventas / Ventas por Sala): solo las salas propias. Esos
     * reportes muestran la economía completa de la sala (comisión de
     * plataforma y de sala, neto) — dato del dueño de la sala, no de quien
     * vendió cartones en ella. Las ventas de la red en salas ajenas siguen
     * visibles en Cartones/Ganadores (applySaleScope) y en el Resumen
     * Financiero del comercio.
     */
    private function applyRoomScope(Builder $query): Builder
    {
        $scope = $this->ownerScope();

        if ($scope === null) {
            return $query;
        }

        return $query->where($query->getModel()->qualifyColumn('creator_user_id'), $scope['owner']);
    }

    /**
     * Aborta con 403 si la sala pedida (route-model-binding directo, ej.
     * salesReportByRoom) no está dentro del scope del usuario — mismo
     * criterio que applyRoomScope() pero para una sala puntual.
     */
    private function assertRoomInScope(BingoRoom $room): void
    {
        // Vendedor: solo salas donde tiene ventas propias (ver isSellerView()).
        if ($this->isSellerView()) {
            abort_unless(
                BingoCardLot::where('room_id', $room->id)->where('seller_user_id', (string) Auth::id())->exists(),
                403
            );
            return;
        }

        $scope = $this->ownerScope();

        if ($scope === null) {
            return;
        }

        abort_if((string) $room->creator_user_id !== $scope['owner'], 403);
    }

    /**
     * Etiqueta legible de una sala para las columnas de reporte: no existe un
     * campo "nombre" en bingo_rooms (ver BingoPrizePayoutService, que usa el
     * mismo criterio) — se identifica por su uuid_code corto.
     */
    private function roomLabel(?BingoRoom $room): string
    {
        if ($room === null) {
            return '—';
        }

        return 'Sala #' . strtoupper(substr((string) $room->uuid_code, 0, 8));
    }

    /**
     * Etiquetas de estado de BingoCard, compartidas entre el render Inertia
     * (primera página) y el endpoint de paginación (cardsSoldData).
     *
     * @return array<int, string>
     */
    private function cardStatusLabels(): array
    {
        return [
            BingoCard::STATUS_ACTIVE    => 'Active',
            BingoCard::STATUS_INACTIVE  => 'Used',
            BingoCard::STATUS_WINNER    => 'Winner',
            BingoCard::STATUS_CANCELLED => 'Refunded',
        ];
    }

    /**
     * Columnas de la tabla de Cartones Vendidos — compartidas entre el render
     * inicial y el refetch paginado para que ambos formatos coincidan siempre.
     */
    private function cardsSoldColumns(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'card_number', 'label' => 'Número de Cartón', 'sortable' => true],
            ['key' => 'room_name', 'label' => 'Sala', 'sortable' => true],
            ['key' => 'player_name', 'label' => 'Jugador', 'sortable' => true],
            ['key' => 'sale_date', 'label' => 'Fecha de Venta', 'sortable' => true],
            ['key' => 'amount', 'label' => 'Monto', 'sortable' => true],
            ['key' => 'currency', 'label' => 'Moneda', 'sortable' => true],
            ['key' => 'status', 'label' => 'Estado', 'sortable' => true],
        ];
    }

    /**
     * Query base del reporte de Cartones Vendidos, con los mismos filtros
     * aplicados tanto en el render Inertia como en el refetch server-side.
     */
    private function cardsSoldQuery(?string $currency, ?string $dateFilter): \Illuminate\Database\Eloquent\Builder
    {
        $query = BingoCard::query()
            ->with(['room', 'roomUser.user', 'currency', 'cardLot'])
            ->orderByDesc('created_at');

        $this->applySaleScope($query);

        if ($currency) {
            $query->where('currency_id', $currency);
        }

        if ($dateFilter) {
            $query->whereDate('created_at', $dateFilter);
        }

        return $query;
    }

    /**
     * Mapea un único BingoCard al shape de fila del reporte — usado tanto por
     * mapCardsSoldRows() (páginas ya cargadas en memoria) como por
     * cardsSoldExport() (cursor, una fila a la vez, sin paginar).
     */
    private function mapCardsSoldRow(BingoCard $card, array $cardStatusToLabel): array
    {
        return [
            'id'          => (string) $card->id,
            'card_number' => $card->serial_number,
            'room_name'   => $this->roomLabel($card->room),
            'player_name' => $card->roomUser?->user?->name ?? '—',
            'sale_date'   => optional($card->created_at)->format('Y-m-d H:i:s'),
            'amount'      => (float) ($card->cardLot?->card_price ?? 0),
            'currency'    => $card->currency?->iso_code ?? '—',
            'status'      => $cardStatusToLabel[(int) $card->status] ?? (string) $card->status,
        ];
    }

    /**
     * Mapea una página de BingoCard al shape de fila del reporte.
     *
     * @param Collection<int, BingoCard> $cards
     */
    private function mapCardsSoldRows(Collection $cards): array
    {
        $cardStatusToLabel = $this->cardStatusLabels();

        return $cards
            ->map(fn (BingoCard $card) => $this->mapCardsSoldRow($card, $cardStatusToLabel))
            ->all();
    }

    /**
     * Shape estándar {total, per_page, current_page, last_page, from, to} —
     * mismo formato usado por BingoRoomContextService::resolveSummaryGameRooms
     * para que el frontend comparta un único tipo Pagination.
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
        ];
    }

    /**
     * Display the cards sold report page.
     */
    public function cardsSold(?string $currency = null, ?string $dateFilter = null): Response
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        // Antes traía hasta 500 filas resueltas en un solo request (truncando
        // silenciosamente ventas por encima de ese límite) — ahora solo la
        // primera página, igual que el refetch de cardsSoldData().
        $paginator = $this->cardsSoldQuery($currency, $dateFilter)->paginate(15);

        $cardsData = [
            'columns' => $this->cardsSoldColumns(),
            'rows'    => $this->mapCardsSoldRows($paginator->getCollection()),
        ];

        $currencies = Currency::query()->orderBy('iso_code')->get(['id', 'iso_code']);

        return Inertia::render('Games/Bingo/Report/CardsSold', [
            'title' => 'Reporte de Cartones Vendidos',
            'cards' => $cardsData,
            'cards_pagination' => $this->paginationMeta($paginator),
            'currencies' => $currencies,
            'selectedCurrency' => $currency,
            'dateFilter' => $dateFilter
        ]);
    }

    /**
     * Refetch paginado/filtrado del reporte de Cartones Vendidos, usado por
     * la tabla en modo server-side tras la carga inicial de la página —
     * mismo patrón que BingoRoomController::summaryData().
     */
    public function cardsSoldData(Request $request): JsonResponse
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $currency   = $request->query('currency');
        $dateFilter = $request->query('dateFilter');
        $perPage    = (int) $request->query('per_page', 15);
        $page       = (int) $request->query('page', 1);

        $paginator = $this->cardsSoldQuery($currency, $dateFilter)->paginate($perPage, ['*'], 'page', $page);

        return $this->handleServiceJson(fn () => [
            'cards' => [
                'columns' => $this->cardsSoldColumns(),
                'rows'    => $this->mapCardsSoldRows($paginator->getCollection()),
            ],
            'pagination' => $this->paginationMeta($paginator),
        ]);
    }

    /**
     * Exporta el reporte completo de Cartones Vendidos a CSV, con los mismos
     * filtros que la vista — usa cursor() para no materializar en memoria el
     * dataset completo (a diferencia de cardsSold()/cardsSoldData(), que solo
     * traen una página).
     */
    public function cardsSoldExport(Request $request): StreamedResponse
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $currency   = $request->query('currency');
        $dateFilter = $request->query('dateFilter');
        $cardStatusToLabel = $this->cardStatusLabels();

        return $this->streamCsvExport(
            'bingo-cartones-vendidos',
            ['ID', 'Número de Cartón', 'Sala', 'Jugador', 'Fecha de Venta', 'Monto', 'Moneda', 'Estado'],
            $this->cardsSoldQuery($currency, $dateFilter)->cursor(),
            fn ($card) => $this->mapCardsSoldRow($card, $cardStatusToLabel),
        );
    }

    /**
     * Etiquetas de estado de BingoCardPrize — compartidas entre el render
     * Inertia y el refetch paginado.
     *
     * @return array<string, string>
     */
    private function prizeStatusLabels(): array
    {
        return [
            'waiting'    => 'Pending',
            'processing' => 'Processing',
            'claimed'    => 'Paid',
            'cancelled'  => 'Cancelled',
            'suspended'  => 'Suspended',
        ];
    }

    /**
     * Columnas de la tabla de Ganadores — compartidas entre el render inicial
     * y el refetch paginado.
     */
    private function winnersColumns(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'winner_name', 'label' => 'Ganador', 'sortable' => true],
            ['key' => 'room_name', 'label' => 'Sala', 'sortable' => true],
            ['key' => 'prize_type', 'label' => 'Tipo de Premio', 'sortable' => true],
            ['key' => 'prize_amount', 'label' => 'Monto del Premio', 'sortable' => true],
            ['key' => 'currency', 'label' => 'Moneda', 'sortable' => true],
            ['key' => 'win_date', 'label' => 'Fecha de Ganancia', 'sortable' => true],
            ['key' => 'status', 'label' => 'Estado', 'sortable' => true],
        ];
    }

    /**
     * Query base del reporte de Ganadores, con los mismos filtros aplicados
     * tanto en el render Inertia como en el refetch server-side.
     */
    private function winnersQuery(?string $type, ?string $currency, ?string $dateFilter): \Illuminate\Database\Eloquent\Builder
    {
        $query = BingoCardPrize::query()
            ->with(['room', 'creatorUser', 'currency'])
            ->orderByDesc('created_at');

        $this->applySaleScope($query);

        if ($type) {
            $query->where('prize_type', $type);
        }

        if ($currency) {
            $query->where('currency_id', $currency);
        }

        if ($dateFilter) {
            $query->whereDate('created_at', $dateFilter);
        }

        return $query;
    }

    /**
     * Mapea un único BingoCardPrize al shape de fila del reporte — usado
     * tanto por mapWinnersRows() como por winnersExport() (cursor).
     */
    private function mapWinnersRow(BingoCardPrize $prize, array $prizeStatusToLabel): array
    {
        return [
            'id'           => (string) $prize->id,
            'winner_name'  => $prize->creatorUser?->name ?? '—',
            'room_name'    => $this->roomLabel($prize->room),
            'prize_type'   => $prize->prize_type_description,
            'prize_amount' => (float) $prize->local_amount,
            'currency'     => $prize->currency?->iso_code ?? '—',
            'win_date'     => optional($prize->claimed_at ?? $prize->created_at)->format('Y-m-d H:i:s'),
            'status'       => $prizeStatusToLabel[$prize->prize_status] ?? $prize->prize_status,
        ];
    }

    /**
     * Mapea una página de BingoCardPrize al shape de fila del reporte.
     *
     * @param Collection<int, BingoCardPrize> $prizes
     */
    private function mapWinnersRows(Collection $prizes): array
    {
        $prizeStatusToLabel = $this->prizeStatusLabels();

        return $prizes
            ->map(fn (BingoCardPrize $prize) => $this->mapWinnersRow($prize, $prizeStatusToLabel))
            ->all();
    }

    /**
     * Display the winners report page.
     */
    public function winners(?string $type = null, ?string $dateFilter = null, ?string $currency = null): Response
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        // Igual que cardsSold(): primera página en vez de limit(500), que
        // truncaba silenciosamente ganadores por encima del límite.
        $paginator = $this->winnersQuery($type, $currency, $dateFilter)->paginate(15);

        $winnersData = [
            'columns' => $this->winnersColumns(),
            'rows'    => $this->mapWinnersRows($paginator->getCollection()),
        ];

        $currencies = Currency::query()->orderBy('iso_code')->get(['id', 'iso_code']);

        $prizeTypes = collect(BingoCardPrize::PRIZE_TYPES)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();

        return Inertia::render('Games/Bingo/Report/Winners', [
            'title' => 'Reporte de Ganadores',
            'winners' => $winnersData,
            'winners_pagination' => $this->paginationMeta($paginator),
            'currencies' => $currencies,
            'prizeTypes' => $prizeTypes,
            'selectedType' => $type,
            'dateFilter' => $dateFilter,
            'selectedCurrency' => $currency
        ]);
    }

    /**
     * Refetch paginado/filtrado del reporte de Ganadores, usado por la tabla
     * en modo server-side tras la carga inicial de la página.
     */
    public function winnersData(Request $request): JsonResponse
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $type       = $request->query('type');
        $currency   = $request->query('currency');
        $dateFilter = $request->query('dateFilter');
        $perPage    = (int) $request->query('per_page', 15);
        $page       = (int) $request->query('page', 1);

        $paginator = $this->winnersQuery($type, $currency, $dateFilter)->paginate($perPage, ['*'], 'page', $page);

        return $this->handleServiceJson(fn () => [
            'winners' => [
                'columns' => $this->winnersColumns(),
                'rows'    => $this->mapWinnersRows($paginator->getCollection()),
            ],
            'pagination' => $this->paginationMeta($paginator),
        ]);
    }

    /**
     * Exporta el reporte completo de Ganadores a CSV, con los mismos filtros
     * que la vista — usa cursor() para no materializar el dataset completo.
     */
    public function winnersExport(Request $request): StreamedResponse
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $type       = $request->query('type');
        $currency   = $request->query('currency');
        $dateFilter = $request->query('dateFilter');
        $prizeStatusToLabel = $this->prizeStatusLabels();

        return $this->streamCsvExport(
            'bingo-ganadores',
            ['ID', 'Ganador', 'Sala', 'Tipo de Premio', 'Monto del Premio', 'Moneda', 'Fecha de Ganancia', 'Estado'],
            $this->winnersQuery($type, $currency, $dateFilter)->cursor(),
            fn ($prize) => $this->mapWinnersRow($prize, $prizeStatusToLabel),
        );
    }

    /**
     * Columnas de la tabla de Ventas — compartidas entre el render inicial y
     * el refetch paginado.
     */
    private function salesReportColumns(): array
    {
        return array_map(
            fn (string $key, string $label) => ['key' => $key, 'label' => $label, 'sortable' => true],
            array_keys($this->salesReportLabels()),
            $this->salesReportLabels(),
        );
    }

    /**
     * Etiquetas de columna del reporte de Ventas (tabla y CSV). Para el
     * vendedor, "Comisión" es SU comisión y el neto es lo que debe entregar al
     * comercio — mismo vocabulario que el formulario de generar cartones.
     *
     * @return array<string, string> key => label
     */
    private function salesReportLabels(): array
    {
        $seller = $this->isSellerView();

        return [
            'id'               => 'ID',
            'room_name'        => 'Sala',
            'room_type'        => 'Tipo de Sala',
            'total_cards_sold' => 'Cartones Vendidos',
            'total_amount'     => 'Monto Total',
            'currency'         => 'Moneda',
            'commission'       => $seller ? 'Mi Comisión' : 'Comisión',
            'net_amount'       => $seller ? 'Total Banca' : 'Monto Neto',
            'sale_date'        => $seller ? 'Última Venta' : 'Fecha',
            'status'           => 'Estado',
        ];
    }

    /**
     * Página del reporte de Ventas (filas + resumen + paginación), común al
     * render Inertia y al refetch paginado.
     *
     * @return array{rows: array, summary: array, paginator: LengthAwarePaginator|null}
     */
    private function buildSalesReport(?string $currency, ?string $dateFilter, Collection $currenciesById, int $perPage, int $page): array
    {
        if ($this->isSellerView()) {
            $paginator = $this->sellerSalesRowsQuery($dateFilter, $currency)->paginate($perPage, ['*'], 'page', $page);

            return [
                'rows'      => $this->mapSellerSalesRows($paginator->getCollection(), $currenciesById),
                'summary'   => $this->sellerSalesSummary($dateFilter, $currency, $currenciesById),
                'paginator' => $paginator,
            ];
        }

        $paginator = $this->salesReportRoomsQuery($dateFilter)->paginate($perPage, ['*'], 'page', $page);

        return [
            'rows'      => $this->mapSalesReportRows($paginator->getCollection(), $currency, $currenciesById),
            'summary'   => $this->salesReportSummary($dateFilter, $currency, $currenciesById),
            'paginator' => $paginator,
        ];
    }

    /**
     * Lotes de venta propios del vendedor (base del reporte de Ventas en
     * vista vendedor). Excluye lotes REFUNDED — venta revertida, mismo
     * criterio que CheckExpiredBingoRoomsService. El filtro de fecha es por
     * fecha de VENTA (created_at del lote), no de creación de la sala.
     */
    private function sellerSalesQuery(?string $dateFilter, ?string $currency): Builder
    {
        return BingoCardLot::query()
            ->where('seller_user_id', (string) Auth::id())
            ->where('status', '!=', 'REFUNDED')
            ->when($dateFilter, fn ($q) => $q->whereDate('created_at', $dateFilter))
            ->when($currency, fn ($q) => $q->where('currency_id', $currency));
    }

    /**
     * Una fila por sala+moneda (mismo shape que mapSalesReportRows()), con
     * los totales de las ventas del vendedor — su comisión real
     * (commission_amount del lote), no las comisiones de sala/plataforma.
     */
    private function sellerSalesRowsQuery(?string $dateFilter, ?string $currency): Builder
    {
        return $this->sellerSalesQuery($dateFilter, $currency)
            ->select(['room_id', 'currency_id'])
            ->selectRaw('SUM(card_count) as cards_sold, SUM(sale_amount) as total_amount, SUM(commission_amount) as commission_total, MAX(created_at) as last_sale_at')
            ->groupBy('room_id', 'currency_id')
            ->with([
                'room:id,uuid_code,status_id',
                'room.status:id,code',
                'room.config:id,room_id,room_type_id',
                'room.config.roomType:id,code',
            ])
            ->orderByDesc('last_sale_at');
    }

    /**
     * @param Collection<int, BingoCardLot> $groups Filas agrupadas de sellerSalesRowsQuery()
     */
    private function mapSellerSalesRows(Collection $groups, Collection $currenciesById): array
    {
        return $groups->map(function (BingoCardLot $group) use ($currenciesById) {
            $amount     = (float) $group->getAttribute('total_amount');
            $commission = (float) $group->getAttribute('commission_total');
            $lastSaleAt = $group->getAttribute('last_sale_at');

            return [
                'id'               => ($group->room?->getRouteKey() ?? (string) $group->room_id) . ':' . $group->currency_id,
                'room_name'        => $this->roomLabel($group->room),
                'room_type'        => $group->room?->config?->roomType?->code ?? 'PUBLIC',
                'total_cards_sold' => (int) $group->getAttribute('cards_sold'),
                'total_amount'     => $amount,
                'currency'         => $currenciesById->get($group->currency_id)?->iso_code ?? '—',
                'commission'       => $commission,
                'net_amount'       => $amount - $commission,
                'sale_date'        => $lastSaleAt ? Carbon::parse($lastSaleAt)->format('Y-m-d') : '—',
                'status'           => $group->room?->status?->code ?? '—',
            ];
        })->values()->all();
    }

    /**
     * Resumen del reporte de Ventas en vista vendedor — mismo shape que
     * salesReportSummary(), agregado en SQL sobre TODAS sus ventas que
     * matchean los filtros (no solo la página visible).
     */
    private function sellerSalesSummary(?string $dateFilter, ?string $currency, Collection $currenciesById): array
    {
        $byCurrency = $this->sellerSalesQuery($dateFilter, $currency)
            ->toBase()
            ->selectRaw('currency_id, SUM(card_count) as cards_sold, SUM(sale_amount) as total_amount, SUM(commission_amount) as commission_total')
            ->groupBy('currency_id')
            ->get();

        $totalsByCurrency = [];
        $totalCardsSold = 0;

        foreach ($byCurrency as $row) {
            $isoCode = $currenciesById->get($row->currency_id)?->iso_code ?? '—';
            $totalsByCurrency[$isoCode] ??= ['amount' => 0.0, 'commission' => 0.0];
            $totalsByCurrency[$isoCode]['amount'] += (float) $row->total_amount;
            $totalsByCurrency[$isoCode]['commission'] += (float) $row->commission_total;
            $totalCardsSold += (int) $row->cards_sold;
        }

        return [
            'total_rooms'      => (int) $this->sellerSalesQuery($dateFilter, $currency)->distinct()->count('room_id'),
            'total_cards_sold' => $totalCardsSold,
            'by_currency'      => $totalsByCurrency,
            'total_commission' => array_sum(array_column($totalsByCurrency, 'commission')),
        ];
    }

    /**
     * Query base del reporte de Ventas — se pagina sobre SALAS (no sobre las
     * filas expandidas por moneda): metrics_by_currency es un array JSON por
     * sala (ver RoomMetricsService::registerSale), así que paginar después de
     * expandir dejaría el conteo de páginas dependiendo de cuántas monedas
     * vendió cada sala, en vez de cuántas salas hay.
     */
    private function salesReportRoomsQuery(?string $dateFilter): \Illuminate\Database\Eloquent\Builder
    {
        $query = BingoRoom::query()
            ->with(['config.roomType', 'status'])
            ->orderByDesc('created_at');

        $this->applyRoomScope($query);

        if ($dateFilter) {
            $query->whereDate('created_at', $dateFilter);
        }

        return $query;
    }

    /**
     * Expande una página de salas en filas por moneda (una fila por cada
     * entrada de metrics_by_currency), aplicando el filtro de moneda si
     * corresponde. No calcula totales — eso lo hace salesReportSummary()
     * sobre el dataset COMPLETO, no solo la página visible.
     *
     * @param Collection<int, BingoRoom> $rooms
     */
    private function mapSalesReportRows(Collection $rooms, ?string $currency, Collection $currenciesById): array
    {
        $rows = [];

        foreach ($rooms as $room) {
            /** @var BingoRoom $room */
            $metricsByCurrency = (array) ($room->metrics_by_currency ?? []);

            foreach ($metricsByCurrency as $currencyId => $metrics) {
                if ($currency && (string) $currencyId !== (string) $currency) {
                    continue;
                }

                $isoCode = $currenciesById->get($currencyId)?->iso_code ?? '—';
                $totalSales = (float) ($metrics['total_sales'] ?? 0);
                $commission = (float) ($metrics['rke_commission'] ?? 0) + (float) ($metrics['room_commission'] ?? 0);
                $cardsSold = (int) ($metrics['total_cards'] ?? 0);

                $rows[] = [
                    // getRouteKey() (hash), no el id crudo: Sales.vue arma
                    // /bingo/sales-report/room/{room} con la parte previa a
                    // ':' y el binding de BingoRoom (HasHashedRouteKey) solo
                    // decodifica hashes — con el id crudo daba 404.
                    'id'               => $room->getRouteKey() . ':' . $currencyId,
                    'room_name'        => $this->roomLabel($room),
                    'room_type'        => $room->config?->roomType?->code ?? 'PUBLIC',
                    'total_cards_sold' => $cardsSold,
                    'total_amount'     => $totalSales,
                    'currency'         => $isoCode,
                    'commission'       => $commission,
                    'net_amount'       => $totalSales - $commission,
                    'sale_date'        => optional($room->created_at)->format('Y-m-d'),
                    'status'           => $room->status?->code ?? '—',
                ];
            }
        }

        return $rows;
    }

    /**
     * Totales reales del reporte de Ventas, agregados sobre TODAS las salas
     * que matchean los filtros (no solo la página visible) — sin esto, el
     * "Resumen de Ventas" mostraría solo un subconjunto arbitrario según qué
     * página esté abierta. Solo trae la columna metrics_by_currency (JSON
     * pequeño por fila) en vez de repetir el with() de relaciones de
     * salesReportRoomsQuery(), que sería innecesariamente pesado para un
     * simple sumatorio.
     */
    private function salesReportSummary(?string $dateFilter, ?string $currency, Collection $currenciesById): array
    {
        $query = BingoRoom::query()->select(['id', 'metrics_by_currency']);

        $this->applyRoomScope($query);

        if ($dateFilter) {
            $query->whereDate('created_at', $dateFilter);
        }

        $totalsByCurrency = [];
        $totalCardsSold = 0;
        $roomsWithSales = 0;

        // chunkById evita cargar todas las salas en memoria a la vez cuando el
        // histórico crece — cada chunk solo trae id + metrics_by_currency.
        $query->orderBy('id')->chunkById(500, function (\Illuminate\Support\Collection $rooms) use (
            &$totalsByCurrency, &$totalCardsSold, &$roomsWithSales, $currency, $currenciesById
        ) {
            foreach ($rooms as $room) {
                /** @var BingoRoom $room */
                $metricsByCurrency = (array) ($room->metrics_by_currency ?? []);

                if (empty($metricsByCurrency)) {
                    continue;
                }

                $roomHasMatchingSale = false;

                foreach ($metricsByCurrency as $currencyId => $metrics) {
                    if ($currency && (string) $currencyId !== (string) $currency) {
                        continue;
                    }

                    $isoCode = $currenciesById->get($currencyId)?->iso_code ?? '—';
                    $totalSales = (float) ($metrics['total_sales'] ?? 0);
                    $commission = (float) ($metrics['rke_commission'] ?? 0) + (float) ($metrics['room_commission'] ?? 0);

                    $totalsByCurrency[$isoCode] ??= ['amount' => 0.0, 'commission' => 0.0];
                    $totalsByCurrency[$isoCode]['amount'] += $totalSales;
                    $totalsByCurrency[$isoCode]['commission'] += $commission;
                    $totalCardsSold += (int) ($metrics['total_cards'] ?? 0);
                    $roomHasMatchingSale = true;
                }

                if ($roomHasMatchingSale) {
                    $roomsWithSales++;
                }
            }
        });

        return [
            'total_rooms'      => $roomsWithSales,
            'total_cards_sold' => $totalCardsSold,
            'by_currency'      => $totalsByCurrency,
            'total_commission' => array_sum(array_column($totalsByCurrency, 'commission')),
        ];
    }

    /**
     * Display the sales report page.
     */
    public function salesReport(?string $currency = null, ?string $dateFilter = null): Response
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $currenciesById = Currency::query()->orderBy('iso_code')->get(['id', 'iso_code'])->keyBy('id');

        // Se pagina sobre salas (15 por página); metrics_by_currency de cada
        // sala se expande después, solo para las salas de esta página. En
        // vista vendedor, sobre sus propias ventas agrupadas por sala+moneda.
        $report = $this->buildSalesReport($currency, $dateFilter, $currenciesById, 15, 1);
        $paginator = $report['paginator'];

        $salesData = [
            'columns' => $this->salesReportColumns(),
            'rows'    => $report['rows'],
            'summary' => $report['summary'],
        ];

        $roomTypes = \App\Models\Games\Common\Room\RoomType::query()
            ->get(['code', 'label'])
            ->map(fn ($rt) => ['value' => $rt->code, 'label' => $rt->label])
            ->all();

        return Inertia::render('Games/Bingo/Report/Sales', [
            'title' => 'Reporte de Ventas',
            'sales' => $salesData,
            'sales_pagination' => $this->paginationMeta($paginator),
            'currencies' => $currenciesById->values(),
            'roomTypes' => $roomTypes,
            'selectedCurrency' => $currency,
            'dateFilter' => $dateFilter,
            'sellerView' => $this->isSellerView(),
        ]);
    }

    /**
     * Refetch paginado/filtrado del reporte de Ventas, usado por la tabla en
     * modo server-side tras la carga inicial de la página. El summary se
     * recalcula sobre el dataset completo en cada refetch (misma lógica que
     * el render Inertia) para reflejar siempre los filtros activos, no solo
     * la página visible.
     */
    public function salesReportData(Request $request): JsonResponse
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $currency   = $request->query('currency');
        $dateFilter = $request->query('dateFilter');
        $perPage    = (int) $request->query('per_page', 15);
        $page       = (int) $request->query('page', 1);

        $currenciesById = Currency::query()->orderBy('iso_code')->get(['id', 'iso_code'])->keyBy('id');

        $report = $this->buildSalesReport($currency, $dateFilter, $currenciesById, $perPage, $page);

        return $this->handleServiceJson(fn () => [
            'sales' => [
                'columns' => $this->salesReportColumns(),
                'rows'    => $report['rows'],
                'summary' => $report['summary'],
            ],
            'pagination' => $this->paginationMeta($report['paginator']),
        ]);
    }

    /**
     * Exporta el reporte completo de Ventas a CSV, con los mismos filtros que
     * la vista — una fila por sala+moneda, igual que mapSalesReportRows(),
     * pero recorriendo TODAS las salas vía cursor() en vez de una página.
     */
    public function salesReportExport(Request $request): StreamedResponse
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $currency   = $request->query('currency');
        $dateFilter = $request->query('dateFilter');
        $currenciesById = Currency::query()->orderBy('iso_code')->get(['id', 'iso_code'])->keyBy('id');

        $rows = (function () use ($dateFilter, $currency, $currenciesById) {
            if ($this->isSellerView()) {
                // get() y no cursor(): cursor() ignora los with() (N+1 por
                // fila), y el dataset agrupado de un solo vendedor es chico
                // (una fila por sala+moneda donde vendió).
                yield from $this->mapSellerSalesRows(
                    $this->sellerSalesRowsQuery($dateFilter, $currency)->get(),
                    $currenciesById,
                );
                return;
            }

            foreach ($this->salesReportRoomsQuery($dateFilter)->cursor() as $room) {
                foreach ($this->mapSalesReportRows(collect([$room]), $currency, $currenciesById) as $row) {
                    yield $row;
                }
            }
        })();

        return $this->streamCsvExport(
            'bingo-ventas',
            array_values($this->salesReportLabels()),
            $rows,
            fn (array $row) => $row,
        );
    }

    /**
     * Etiquetas de estado de pago (BingoCardPayment) — compartidas entre el
     * render Inertia y el refetch paginado.
     *
     * @return array<string, string>
     */
    private function paymentStatusLabels(): array
    {
        return [
            'PENDING'   => 'Pending',
            // Enum real de bingo_card_payments.payment_status.
            'APPROVED'  => 'Paid',
            'REJECTED'  => 'Cancelled',
            'CANCELLED' => 'Cancelled',
        ];
    }

    /**
     * Columnas de la tabla de Ventas por Sala — compartidas entre el render
     * inicial y el refetch paginado.
     */
    private function salesByRoomColumns(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'card_number', 'label' => 'Número de Cartón', 'sortable' => true],
            ['key' => 'player_name', 'label' => 'Jugador', 'sortable' => true],
            ['key' => 'card_price', 'label' => 'Precio Cartón', 'sortable' => true],
            ['key' => 'currency', 'label' => 'Moneda', 'sortable' => true],
            ['key' => 'exchange_rate', 'label' => 'Tasa (1 USD)', 'sortable' => true],
            ['key' => 'sale_date', 'label' => 'Fecha de Venta', 'sortable' => true],
            ['key' => 'payment_method', 'label' => 'Método de Pago', 'sortable' => true],
            ['key' => 'status', 'label' => 'Estado', 'sortable' => true],
        ];
    }

    /**
     * Query base del reporte de Ventas por Sala, con los mismos filtros
     * aplicados tanto en el render Inertia como en el refetch server-side.
     */
    private function salesByRoomQuery(BingoRoom $room, ?string $currency): \Illuminate\Database\Eloquent\Builder
    {
        $query = BingoCard::query()
            ->where('room_id', $room->id)
            ->with(['roomUser.user', 'currency', 'cardLot.payments'])
            ->orderByDesc('created_at');

        // Vendedor: solo sus cartones de esta sala, no los de otros vendedores.
        if ($this->isSellerView()) {
            $query->where('seller_user_id', (string) Auth::id());
        }

        if ($currency) {
            $query->where('currency_id', $currency);
        }

        return $query;
    }

    /**
     * Mapea un único BingoCard al shape de fila del reporte por sala — usado
     * tanto por mapSalesByRoomRows() como por salesReportByRoomExport() (cursor).
     */
    private function mapSalesByRoomRow(BingoCard $card, array $paymentStatusToLabel): array
    {
        $payment = $card->cardLot?->payments?->first();

        return [
            'id'             => (string) $card->id,
            'card_number'    => $card->serial_number,
            'player_name'    => $card->roomUser?->user?->name ?? '—',
            'card_price'     => (float) ($card->cardLot?->card_price ?? 0),
            'currency'       => $card->currency?->iso_code ?? '—',
            // Tasa con la que se cobró (snapshot del lote).
            'exchange_rate'  => $card->cardLot?->exchange_rate !== null ? round((float) $card->cardLot->exchange_rate, 4) : null,
            'sale_date'      => optional($card->created_at)->format('Y-m-d H:i:s'),
            'payment_method' => $payment?->payment_method ?? '—',
            'status'         => $paymentStatusToLabel[$payment?->payment_status] ?? ($payment?->payment_status ?? 'Pending'),
        ];
    }

    /**
     * Mapea una página de BingoCard al shape de fila del reporte por sala.
     *
     * @param Collection<int, BingoCard> $cards
     */
    private function mapSalesByRoomRows(Collection $cards): array
    {
        $paymentStatusToLabel = $this->paymentStatusLabels();

        return $cards
            ->map(fn (BingoCard $card) => $this->mapSalesByRoomRow($card, $paymentStatusToLabel))
            ->all();
    }

    /**
     * Totales de la sala (room_info), agregados con una query de agregación
     * SQL sobre TODOS los cartones de la sala que matchean el filtro de
     * moneda — no solo la página visible, igual criterio que
     * salesReportSummary(). primaryCurrency se resuelve por separado porque
     * es un dato de la fila más reciente, no algo agregable.
     */
    private function salesByRoomInfo(BingoRoom $room, ?string $currency): array
    {
        if ($this->isSellerView()) {
            return $this->sellerSalesByRoomInfo($room, $currency);
        }

        // Columnas calificadas: bingo_card_lots también tiene room_id y
        // currency_id — sin prefijo MySQL rechazaba el query por ambiguo (1052).
        $totalsQuery = BingoCard::query()
            ->where('bingo_cards.room_id', $room->id)
            ->when($currency, fn ($q) => $q->where('bingo_cards.currency_id', $currency))
            ->join('bingo_card_lots', 'bingo_card_lots.id', '=', 'bingo_cards.card_lot_id');

        $totals = (clone $totalsQuery)
            ->selectRaw('COUNT(bingo_cards.id) as cards_count, COALESCE(SUM(bingo_card_lots.card_price), 0) as total_amount')
            ->first();

        $primaryCurrency = BingoCard::query()
            ->where('room_id', $room->id)
            ->when($currency, fn ($q) => $q->where('currency_id', $currency))
            ->with('currency')
            ->orderByDesc('created_at')
            ->first()
            ?->currency?->iso_code ?? '—';

        $totalAmount = (float) ($totals?->total_amount ?? 0);
        $commission = (float) ($room->room_commission ?? 0) + (float) ($room->rke_commission ?? 0);

        return [
            'id'               => (string) $room->id,
            'name'             => $this->roomLabel($room),
            'type'             => $room->config?->roomType?->code ?? 'PUBLIC',
            'total_cards_sold' => (int) ($totals?->cards_count ?? 0),
            'total_amount'     => $totalAmount,
            'currency'         => $primaryCurrency,
            'commission'       => $commission,
            'net_amount'       => $totalAmount - $commission,
        ];
    }

    /**
     * room_info en vista vendedor: totales de SUS lotes en la sala (sin
     * REFUNDED) y SU comisión — no room_commission/rke_commission de la sala,
     * que son datos del comercio/plataforma. net_amount = lo que debe entregar
     * al comercio (mismo "Total Banca" que el reporte de Ventas).
     */
    private function sellerSalesByRoomInfo(BingoRoom $room, ?string $currency): array
    {
        $lots = $this->sellerSalesQuery(null, $currency)->where('room_id', $room->id);

        $totals = (clone $lots)
            ->toBase()
            ->selectRaw('COALESCE(SUM(card_count), 0) as cards_sold, COALESCE(SUM(sale_amount), 0) as total_amount, COALESCE(SUM(commission_amount), 0) as commission_total')
            ->first();

        $primaryCurrency = (clone $lots)->with('currency:id,iso_code')->latest('created_at')->first()?->currency?->iso_code ?? '—';

        $totalAmount = (float) ($totals?->total_amount ?? 0);
        $commission  = (float) ($totals?->commission_total ?? 0);

        return [
            'id'               => (string) $room->id,
            'name'             => $this->roomLabel($room),
            'type'             => $room->config?->roomType?->code ?? 'PUBLIC',
            'total_cards_sold' => (int) ($totals?->cards_sold ?? 0),
            'total_amount'     => $totalAmount,
            'currency'         => $primaryCurrency,
            'commission'       => $commission,
            'net_amount'       => $totalAmount - $commission,
        ];
    }

    /**
     * Display the sales report for a specific room.
     */
    public function salesReportByRoom(BingoRoom $room, ?string $currency = null): Response
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $room->load(['config.roomType']);
        $this->assertRoomInScope($room);

        $paginator = $this->salesByRoomQuery($room, $currency)->paginate(15);

        $roomSalesData = [
            'columns'   => $this->salesByRoomColumns(),
            'rows'      => $this->mapSalesByRoomRows($paginator->getCollection()),
            'room_info' => $this->salesByRoomInfo($room, $currency),
        ];

        $currencies = Currency::query()->orderBy('iso_code')->get(['id', 'iso_code']);

        $paymentMethods = BingoCardPayment::query()
            ->whereNotNull('payment_method')
            ->distinct()
            ->orderBy('payment_method')
            ->pluck('payment_method')
            ->map(fn (string $method) => ['value' => $method, 'label' => $method])
            ->all();

        return Inertia::render('Games/Bingo/Report/SalesByRoom', [
            'title' => "Reporte de Ventas - {$roomSalesData['room_info']['name']}",
            'roomSales' => $roomSalesData,
            'room_sales_pagination' => $this->paginationMeta($paginator),
            'currencies' => $currencies,
            'paymentMethods' => $paymentMethods,
            'selectedCurrency' => $currency,
            // Hash (getRouteKey), no id crudo: SalesByRoom.vue lo usa para
            // sales-report-room-data/{room} y sales-report-room-export/{room},
            // cuyo binding (HasHashedRouteKey) solo decodifica hashes.
            'roomId' => $room->getRouteKey(),
            'sellerView' => $this->isSellerView(),
        ]);
    }

    /**
     * Refetch paginado/filtrado del reporte de Ventas por Sala, usado por la
     * tabla en modo server-side tras la carga inicial de la página.
     */
    public function salesReportByRoomData(BingoRoom $room, Request $request): JsonResponse
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $currency = $request->query('currency');
        $perPage  = (int) $request->query('per_page', 15);
        $page     = (int) $request->query('page', 1);

        $room->load(['config.roomType']);
        $this->assertRoomInScope($room);

        $paginator = $this->salesByRoomQuery($room, $currency)->paginate($perPage, ['*'], 'page', $page);

        return $this->handleServiceJson(fn () => [
            'roomSales' => [
                'columns'   => $this->salesByRoomColumns(),
                'rows'      => $this->mapSalesByRoomRows($paginator->getCollection()),
                'room_info' => $this->salesByRoomInfo($room, $currency),
            ],
            'pagination' => $this->paginationMeta($paginator),
        ]);
    }

    /**
     * Exporta el reporte completo de Ventas de una sala a CSV, con los mismos
     * filtros que la vista — usa cursor() para no materializar el dataset
     * completo (a diferencia de salesReportByRoom()/salesReportByRoomData(),
     * que solo traen una página).
     */
    public function salesReportByRoomExport(BingoRoom $room, Request $request): StreamedResponse
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $room->load(['config.roomType']);
        $this->assertRoomInScope($room);

        $currency = $request->query('currency');
        $paymentStatusToLabel = $this->paymentStatusLabels();

        return $this->streamCsvExport(
            'bingo-ventas-sala-' . $room->uuid_code,
            ['ID', 'Número de Cartón', 'Jugador', 'Precio Cartón', 'Moneda', 'Tasa (1 USD)', 'Fecha de Venta', 'Método de Pago', 'Estado'],
            $this->salesByRoomQuery($room, $currency)->cursor(),
            fn ($card) => $this->mapSalesByRoomRow($card, $paymentStatusToLabel),
        );
    }
}
