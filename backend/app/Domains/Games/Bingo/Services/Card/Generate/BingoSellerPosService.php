<?php

namespace App\Domains\Games\Bingo\Services\Card\Generate;

use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Services\Roles\Player\External\ExternalPlayerLinkService;
use Illuminate\Support\Carbon;

/**
 * Datos de apoyo del punto de venta del vendedor (/bingo-card/generate):
 * clientes frecuentes para autocompletar al comprador y el resumen de sus
 * ventas del día.
 *
 * Todo se filtra por seller_user_id = vendedor autenticado: un vendedor
 * nunca ve compradores ni ventas de otro vendedor, aunque sean de su misma
 * red comercial (datos personales: teléfono y nombre).
 */
final class BingoSellerPosService
{
    private const BUYERS_LIMIT = 8;
    private const SALES_LIMIT = 30;

    public function __construct(
        private readonly ExternalPlayerLinkService $externalPlayerLink,
    ) {}

    /**
     * Compradores del vendedor, agrupados por teléfono + país (un
     * ExternalPlayer existe por sala), del más reciente al más antiguo.
     *
     * @return array<int, array{phone: string, country_id: string, first_name: string, last_name: string, purchases: int, last_purchase_at: string|null}>
     */
    public function frequentBuyers(string $sellerUserId, ?string $search = null): array
    {
        $search = trim((string) $search);

        return ExternalPlayer::query()
            ->where('seller_user_id', $sellerUserId)
            ->whereNotNull('phone')
            ->when(mb_strlen($search) >= 2, function ($q) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $digits = preg_replace('/\D/', '', $search);

                $q->where(function ($w) use ($like, $digits) {
                    $w->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like]);

                    // El teléfono se guarda formateado ("+58 0414-1234567"):
                    // se compara solo por dígitos para que "0414123" coincida.
                    if (strlen($digits) >= 3) {
                        $w->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') LIKE ?", ['%' . $digits . '%']);
                    }
                });
            })
            ->groupBy('phone', 'country_id')
            ->selectRaw('phone, country_id, MAX(first_name) AS first_name, MAX(last_name) AS last_name, COUNT(*) AS purchases, MAX(created_at) AS last_purchase_at')
            ->orderByDesc('last_purchase_at')
            ->limit(self::BUYERS_LIMIT)
            ->get()
            ->map(fn ($row) => [
                'phone'            => (string) $row->phone,
                'country_id'       => (string) $row->country_id,
                'first_name'       => (string) $row->first_name,
                'last_name'        => (string) $row->last_name,
                'purchases'        => (int) $row->purchases,
                'last_purchase_at' => $row->last_purchase_at ? Carbon::parse($row->last_purchase_at)->toISOString() : null,
            ])
            ->all();
    }

    /**
     * Ventas del día del vendedor a compradores externos + totales.
     *
     * @return array{sales: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    public function todaySales(string $sellerUserId): array
    {
        $payments = BingoCardPayment::query()
            ->where('seller_user_id', $sellerUserId)
            ->whereNotNull('external_player_id')
            ->where('created_at', '>=', now()->startOfDay())
            ->with([
                'room:id,uuid_code',
                'externalPlayer:id,first_name,last_name,phone,link',
                'cardLot:id,card_count,exchange_rate',
                'currency:id,iso_code,symbol',
            ])
            ->latest('created_at')
            ->get();

        $sales = $payments->take(self::SALES_LIMIT)->map(fn (BingoCardPayment $p) => [
            'id'              => (string) $p->id,
            'status'          => $p->payment_status,
            'room_code'       => $p->room?->uuid_code,
            'buyer_name'      => trim(($p->externalPlayer?->first_name ?? '') . ' ' . ($p->externalPlayer?->last_name ?? '')),
            'buyer_phone'     => $p->externalPlayer?->phone,
            'player_link'     => $p->externalPlayer?->link ? $this->externalPlayerLink->resolveGameUrl($p->externalPlayer->link) : null,
            'card_count'      => (int) ($p->cardLot?->card_count ?? 0),
            'amount'          => (float) $p->payment_amount,
            'currency_symbol' => $p->currency?->symbol ?? '',
            // Tasa con la que se cobró (snapshot del lote); null en USD.
            'exchange_rate'   => $p->cardLot?->exchange_rate && (float) $p->cardLot->exchange_rate !== 1.0
                ? (float) $p->cardLot->exchange_rate
                : null,
            'receipt'         => $p->transaction_reference,
            'created_at'      => $p->created_at?->toISOString(),
        ])->values()->all();

        // Los rechazados/cancelados no suman a lo cobrado ni a los cartones.
        $valid = $payments->whereIn('payment_status', ['PENDING', 'APPROVED']);

        return [
            'sales'   => $sales,
            'summary' => [
                'sales_count' => $valid->count(),
                'cards_count' => (int) $valid->sum(fn (BingoCardPayment $p) => (int) ($p->cardLot?->card_count ?? 0)),
                'pending'     => $payments->where('payment_status', 'PENDING')->count(),
                'approved'    => $payments->where('payment_status', 'APPROVED')->count(),
                'rejected'    => $payments->whereIn('payment_status', ['REJECTED', 'CANCELLED'])->count(),
                // Una fila por moneda: un vendedor puede cobrar en Bs y en USD el mismo día.
                'totals'      => $valid
                    ->groupBy(fn (BingoCardPayment $p) => $p->currency?->iso_code ?? '—')
                    ->map(fn ($group, $iso) => [
                        'currency_iso'    => $iso,
                        'currency_symbol' => $group->first()->currency?->symbol ?? '',
                        'amount'          => round((float) $group->sum('payment_amount'), 2),
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }
}
