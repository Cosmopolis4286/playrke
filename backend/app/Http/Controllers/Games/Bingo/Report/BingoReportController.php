<?php

namespace App\Http\Controllers\Games\Bingo\Report;

use App\Http\Controllers\Controller;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Currency;
use Inertia\Inertia;
use Inertia\Response;

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
    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
    ) {
        $this->authorizeResource(BingoRoom::class, 'room');
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
     * Display the cards sold report page.
     */
    public function cardsSold(string $currency = null, string $dateFilter = null): Response
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $query = BingoCard::query()
            ->with(['room', 'roomUser.user', 'currency', 'cardLot'])
            ->orderByDesc('created_at');

        if ($currency) {
            $query->where('currency_id', $currency);
        }

        if ($dateFilter) {
            $query->whereDate('created_at', $dateFilter);
        }

        $cardStatusToLabel = [
            BingoCard::STATUS_ACTIVE    => 'Active',
            BingoCard::STATUS_INACTIVE  => 'Used',
            BingoCard::STATUS_WINNER    => 'Winner',
            BingoCard::STATUS_CANCELLED => 'Refunded',
        ];

        $rows = $query->limit(500)->get()->map(function (BingoCard $card) use ($cardStatusToLabel) {
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
        })->all();

        $cardsData = [
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'sortable' => true],
                ['key' => 'card_number', 'label' => 'Número de Cartón', 'sortable' => true],
                ['key' => 'room_name', 'label' => 'Sala', 'sortable' => true],
                ['key' => 'player_name', 'label' => 'Jugador', 'sortable' => true],
                ['key' => 'sale_date', 'label' => 'Fecha de Venta', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Monto', 'sortable' => true],
                ['key' => 'currency', 'label' => 'Moneda', 'sortable' => true],
                ['key' => 'status', 'label' => 'Estado', 'sortable' => true],
            ],
            'rows' => $rows,
        ];

        $currencies = Currency::query()->orderBy('iso_code')->get(['id', 'iso_code']);

        return Inertia::render('Games/Bingo/Report/CardsSold', [
            'title' => 'Reporte de Cartones Vendidos',
            'cards' => $cardsData,
            'currencies' => $currencies,
            'selectedCurrency' => $currency,
            'dateFilter' => $dateFilter
        ]);
    }

    /**
     * Display the winners report page.
     */
    public function winners(string $type = null, string $dateFilter = null, string $currency = null): Response
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $query = BingoCardPrize::query()
            ->with(['room', 'creatorUser', 'currency'])
            ->orderByDesc('created_at');

        if ($type) {
            $query->where('prize_type', $type);
        }

        if ($currency) {
            $query->where('currency_id', $currency);
        }

        if ($dateFilter) {
            $query->whereDate('created_at', $dateFilter);
        }

        $prizeStatusToLabel = [
            'waiting'    => 'Pending',
            'processing' => 'Processing',
            'claimed'    => 'Paid',
            'cancelled'  => 'Cancelled',
            'suspended'  => 'Suspended',
        ];

        $rows = $query->limit(500)->get()->map(function (BingoCardPrize $prize) use ($prizeStatusToLabel) {
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
        })->all();

        $winnersData = [
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'sortable' => true],
                ['key' => 'winner_name', 'label' => 'Ganador', 'sortable' => true],
                ['key' => 'room_name', 'label' => 'Sala', 'sortable' => true],
                ['key' => 'prize_type', 'label' => 'Tipo de Premio', 'sortable' => true],
                ['key' => 'prize_amount', 'label' => 'Monto del Premio', 'sortable' => true],
                ['key' => 'currency', 'label' => 'Moneda', 'sortable' => true],
                ['key' => 'win_date', 'label' => 'Fecha de Ganancia', 'sortable' => true],
                ['key' => 'status', 'label' => 'Estado', 'sortable' => true],
            ],
            'rows' => $rows,
        ];

        $currencies = Currency::query()->orderBy('iso_code')->get(['id', 'iso_code']);

        $prizeTypes = collect(BingoCardPrize::PRIZE_TYPES)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();

        return Inertia::render('Games/Bingo/Report/Winners', [
            'title' => 'Reporte de Ganadores',
            'winners' => $winnersData,
            'currencies' => $currencies,
            'prizeTypes' => $prizeTypes,
            'selectedType' => $type,
            'dateFilter' => $dateFilter,
            'selectedCurrency' => $currency
        ]);
    }

    /**
     * Display the sales report page.
     */
    public function salesReport(string $currency = null, string $dateFilter = null): Response
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $query = BingoRoom::query()
            ->with(['config.roomType', 'status'])
            ->orderByDesc('created_at');

        if ($dateFilter) {
            $query->whereDate('created_at', $dateFilter);
        }

        $currencies = Currency::query()->orderBy('iso_code')->get(['id', 'iso_code'])->keyBy('id');

        $rows = [];
        $totalsByCurrency = [];
        $totalCardsSold = 0;

        foreach ($query->get() as $room) {
            /** @var BingoRoom $room */
            // Cada sala puede vender en varias monedas — metrics_by_currency
            // (ver RoomMetricsService::registerSale) ya trae el desglose por
            // currency_id sin necesitar reagregar bingo_cards en cada request.
            $metricsByCurrency = (array) ($room->metrics_by_currency ?? []);

            if (empty($metricsByCurrency)) {
                continue;
            }

            foreach ($metricsByCurrency as $currencyId => $metrics) {
                if ($currency && (string) $currencyId !== (string) $currency) {
                    continue;
                }

                $isoCode = $currencies->get($currencyId)?->iso_code ?? '—';
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

                $totalsByCurrency[$isoCode] ??= ['amount' => 0.0, 'commission' => 0.0];
                $totalsByCurrency[$isoCode]['amount'] += $totalSales;
                $totalsByCurrency[$isoCode]['commission'] += $commission;
                $totalCardsSold += $cardsSold;
            }
        }

        $salesData = [
            'columns' => [
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
            ],
            'rows' => $rows,
            'summary' => [
                'total_rooms'      => count(array_unique(array_map(fn (array $r) => explode(':', $r['id'])[0], $rows))),
                'total_cards_sold' => $totalCardsSold,
                'by_currency'      => $totalsByCurrency,
                'total_commission' => array_sum(array_column($totalsByCurrency, 'commission')),
            ],
        ];

        $roomTypes = \App\Models\Games\Common\Room\RoomType::query()
            ->get(['code', 'label'])
            ->map(fn ($rt) => ['value' => $rt->code, 'label' => $rt->label])
            ->all();

        return Inertia::render('Games/Bingo/Report/Sales', [
            'title' => 'Reporte de Ventas',
            'sales' => $salesData,
            'currencies' => $currencies,
            'roomTypes' => $roomTypes,
            'selectedCurrency' => $currency,
            'dateFilter' => $dateFilter
        ]);
    }

    /**
     * Display the sales report for a specific room.
     */
    public function salesReportByRoom(BingoRoom $room, string $currency = null): Response
    {
        abort_if($this->userContext->userHasRole('player'), 403);

        $room->load(['config.roomType']);

        $query = BingoCard::query()
            ->where('room_id', $room->id)
            ->with(['roomUser.user', 'currency', 'cardLot.payments'])
            ->orderByDesc('created_at');

        if ($currency) {
            $query->where('currency_id', $currency);
        }

        $paymentStatusToLabel = [
            'PENDING'   => 'Pending',
            'COMPLETED' => 'Paid',
            'FAILED'    => 'Cancelled',
        ];

        $cards = $query->limit(500)->get();

        $rows = $cards->map(function (BingoCard $card) use ($paymentStatusToLabel) {
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
        })->all();

        $totalAmount = (float) $cards->sum(fn (BingoCard $card) => (float) ($card->cardLot?->card_price ?? 0));
        $primaryCurrency = $cards->first()?->currency?->iso_code ?? '—';
        $commission = (float) ($room->room_commission ?? 0) + (float) ($room->rke_commission ?? 0);

        $roomSalesData = [
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'sortable' => true],
                ['key' => 'card_number', 'label' => 'Número de Cartón', 'sortable' => true],
                ['key' => 'player_name', 'label' => 'Jugador', 'sortable' => true],
                ['key' => 'card_price', 'label' => 'Precio Cartón', 'sortable' => true],
                ['key' => 'currency', 'label' => 'Moneda', 'sortable' => true],
                ['key' => 'sale_date', 'label' => 'Fecha de Venta', 'sortable' => true],
                ['key' => 'payment_method', 'label' => 'Método de Pago', 'sortable' => true],
                ['key' => 'status', 'label' => 'Estado', 'sortable' => true],
            ],
            'rows' => $rows,
            'room_info' => [
                'id'               => (string) $room->id,
                'name'             => $this->roomLabel($room),
                'type'             => $room->config?->roomType?->code ?? 'PUBLIC',
                'total_cards_sold' => $cards->count(),
                'total_amount'     => $totalAmount,
                'currency'         => $primaryCurrency,
                'commission'       => $commission,
                'net_amount'       => $totalAmount - $commission,
            ]
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
            'currencies' => $currencies,
            'paymentMethods' => $paymentMethods,
            'selectedCurrency' => $currency,
            'roomId' => (string) $room->id
        ]);
    }
}
