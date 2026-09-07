<?php

namespace App\Http\Controllers\Games\Bingo\Report;

use App\Http\Controllers\Controller;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Currency;
use App\Models\Roles\Seller;
use App\Services\Roles\RoleUserService;
use App\Traits\StreamsCsvExport;
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

    /**
     * Resuelve el user_id de "dueño" cuyas salas puede ver el usuario actual
     * en estos reportes, o null si no hay restricción (ve todas las salas).
     *
     * Antes estos endpoints solo bloqueaban 'player' (abort_if de cada
     * método público) y las 4 queries base traían TODAS las salas sin
     * filtrar — cualquier 'seller' (tiene el permiso games.bingo requerido
     * por la ruta) podía ver y exportar en CSV las ventas, comisiones y
     * ganadores de TODOS los vendedores/comercios de la plataforma, no solo
     * los propios. Mismo criterio de scope que ya usa
     * BingoRoom::scopeAccessibleBy() (creator_user_id de la sala), pero
     * resolviendo el manager real vía Seller::resolveCommerce() — a
     * diferencia de BingoCardGenerateService::resolveCreatorOrManagerUserId(),
     * esto sí cubre correctamente un seller que reporta a través de una
     * Subsidiary (ver comentario en Seller::managerCommerce()).
     */
    private function resolveReportScope(): ?string
    {
        $userId = (int) Auth::id();
        $role   = $this->roleUserService->getActiveUserRolePrefix($userId);

        return match ($role) {
            'superadmin', 'admin' => null,
            'commerce', 'subsidiary', 'customer' => (string) $userId,
            'seller' => (string) (Seller::where('user_id', $userId)->first()?->resolveCommerce()?->user_id),
            default => (string) $userId,
        };
    }

    /**
     * Aplica el scope de dueño de sala a un query builder sobre BingoRoom
     * (o cualquier relación con belongsTo hacia BingoRoom vía $relation).
     */
    private function applyRoomScope(\Illuminate\Database\Eloquent\Builder $query, ?string $relation = null): \Illuminate\Database\Eloquent\Builder
    {
        $scopeUserId = $this->resolveReportScope();

        if ($scopeUserId === null) {
            return $query;
        }

        if ($relation === null) {
            return $query->accessibleBy((int) $scopeUserId);
        }

        return $query->whereHas($relation, fn ($q) => $q->accessibleBy((int) $scopeUserId));
    }

    /**
     * Aborta con 403 si la sala pedida (route-model-binding directo, ej.
     * salesReportByRoom) no está dentro del scope del usuario — mismo
     * criterio que applyRoomScope() pero para el caso de una sala puntual en
     * vez de una lista filtrada.
     */
    private function assertRoomInScope(BingoRoom $room): void
    {
        $scopeUserId = $this->resolveReportScope();

        if ($scopeUserId === null) {
            return;
        }

        $isPublic = $room->config?->roomType?->code === 'PUBLIC';

        abort_if((string) $room->creator_user_id !== $scopeUserId && !$isPublic, 403);
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

        $this->applyRoomScope($query, 'room');

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

        $this->applyRoomScope($query, 'room');

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
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'room_name', 'label' => 'Sala', 'sortable' => true],
            ['key' => 'room_type', 'label' => 'Tipo de Sala', 'sortable' => true],
            ['key' => 'total_cards_sold', 'label' => 'Cartones Vendidos', 'sortable' => true],
            ['key' => 'total_amount', 'label' => 'Monto Total', 'sortable' => true],
            ['key' => 'currency', 'label' => 'Moneda', 'sortable' => true],
            ['key' => 'commission', 'label' => 'Comisión', 'sortable' => true],
            ['key' => 'net_amount', 'label' => 'Monto Neto', 'sortable' => true],
            ['key' => 'sale_date', 'label' => 'Fecha', 'sortable' => true],
            ['key' => 'status', 'label' => 'Estado', 'sortable' => true],
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
                    'id'               => (string) $room->id . ':' . $currencyId,
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
        // sala se expande después, solo para las salas de esta página.
        $paginator = $this->salesReportRoomsQuery($dateFilter)->paginate(15);

        $salesData = [
            'columns' => $this->salesReportColumns(),
            'rows'    => $this->mapSalesReportRows($paginator->getCollection(), $currency, $currenciesById),
            'summary' => $this->salesReportSummary($dateFilter, $currency, $currenciesById),
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
            'dateFilter' => $dateFilter
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

        $paginator = $this->salesReportRoomsQuery($dateFilter)->paginate($perPage, ['*'], 'page', $page);

        return $this->handleServiceJson(fn () => [
            'sales' => [
                'columns' => $this->salesReportColumns(),
                'rows'    => $this->mapSalesReportRows($paginator->getCollection(), $currency, $currenciesById),
                'summary' => $this->salesReportSummary($dateFilter, $currency, $currenciesById),
            ],
            'pagination' => $this->paginationMeta($paginator),
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
            foreach ($this->salesReportRoomsQuery($dateFilter)->cursor() as $room) {
                foreach ($this->mapSalesReportRows(collect([$room]), $currency, $currenciesById) as $row) {
                    yield $row;
                }
            }
        })();

        return $this->streamCsvExport(
            'bingo-ventas',
            ['ID', 'Sala', 'Tipo de Sala', 'Cartones Vendidos', 'Monto Total', 'Moneda', 'Comisión', 'Monto Neto', 'Fecha', 'Estado'],
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
            'COMPLETED' => 'Paid',
            'FAILED'    => 'Cancelled',
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
        $totalsQuery = BingoCard::query()
            ->where('room_id', $room->id)
            ->when($currency, fn ($q) => $q->where('currency_id', $currency))
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
            'roomId' => (string) $room->id
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
            ['ID', 'Número de Cartón', 'Jugador', 'Precio Cartón', 'Moneda', 'Fecha de Venta', 'Método de Pago', 'Estado'],
            $this->salesByRoomQuery($room, $currency)->cursor(),
            fn ($card) => $this->mapSalesByRoomRow($card, $paymentStatusToLabel),
        );
    }
}
