<?php

namespace App\Services\Roles\Customer\Dashboard;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Wallet\Contracts\WalletRepositoryInterface;
use App\DTOs\Roles\Customer\Dashboard\CustomerDashboardContextDTO;
use App\Services\Roles\Dashboard\Concerns\BuildsSalesTrend;
use App\Services\Roles\Dashboard\NetworkDashboardService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Contexto del dashboard de Customer (cliente API) — crea y opera sus
 * propias salas (BingoRoom.creator_user_id = su user_id) y juega con saldo
 * propio; no tiene red de vendedores. Antes no existía: el
 * DashboardController buscaba Roles/Customer/Dashboard/Index y la página
 * no cargaba.
 */
final class CustomerDashboardContextService
{
    use BuildsSalesTrend;

    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly NetworkDashboardService $network,
    ) {
    }

    public function dashboardContext() : CustomerDashboardContextDTO
    {
        $user = Auth::user();
        $userId = (string) $user?->getKey();
        $now = Carbon::now();

        $ownRoomIds = fn () => BingoRoom::query()->select('id')->where('creator_user_id', $userId);

        return new CustomerDashboardContextDTO(
            userName: $user?->first_name ?? $user?->username ?? 'Cliente',
            stats: $this->buildStats($userId, $ownRoomIds, $now),
            upcomingRooms: $this->network->upcomingOwnRooms($now),
            salesTrend: $this->buildSalesTrend(scope: fn ($q) => $q
                ->whereIn('room_id', $ownRoomIds())
                ->where('status', '!=', BingoCard::STATUS_CANCELLED)),
            recentActivity: $this->buildRecentActivity($ownRoomIds),
            wallets: $this->buildWallets($userId),
            period: ucfirst($now->translatedFormat('F Y')),
        );
    }

    private function buildRecentActivity(callable $ownRoomIds) : array
    {
        return BingoCardLot::whereIn('room_id', $ownRoomIds())
            ->with(['room:id,uuid_code', 'currency:id,symbol,iso_code'])
            ->latest()
            ->limit(10)
            ->get(['id', 'room_id', 'currency_id', 'card_count', 'sale_amount', 'status', 'created_at'])
            ->map(fn (BingoCardLot $lot) => [
                'id' => (string) $lot->getKey(),
                'type' => 'sale',
                'title' => "{$lot->card_count} cartón(es) vendidos",
                'description' => "Sala {$lot->room?->uuid_code}",
                'timestamp' => $lot->created_at->toIso8601String(),
                'meta' => [
                    'status' => strtolower((string) $lot->status),
                    'price' => $lot->currency?->formatAmount((float) $lot->sale_amount) ?? number_format((float) $lot->sale_amount, 2, ',', '.'),
                    'currency' => $lot->currency?->iso_code ?? '',
                ],
            ])
            ->all();
    }

    private function buildStats(string $userId, callable $ownRoomIds, Carbon $now) : array
    {
        $startOfMonth = $now->copy()->startOfMonth();

        $activeRooms = BingoRoom::active()->where('creator_user_id', $userId)->count();

        $cardsToday = (int) $this->ownRoomLots($ownRoomIds)
            ->where('bingo_card_lots.created_at', '>=', $now->copy()->startOfDay())
            ->sum('bingo_card_lots.card_count');

        $salesUsd = $this->network->salesUsd(
            $this->ownRoomLots($ownRoomIds)->where('bingo_card_lots.created_at', '>=', $startOfMonth),
        );

        // Comisión de sala acumulada por RoomMetricsService (moneda local),
        // mismo origen que el dashboard del comercio.
        $commission = $this->network->currencySummary(
            BingoCreatorSale::query()
                ->where('bingo_creator_sales.creator_user_id', $userId)
                ->where('bingo_creator_sales.updated_at', '>=', $startOfMonth)
                ->where('bingo_creator_sales.room_commission', '>', 0),
            'room_commission',
        );

        // Venta cruzada en sus salas públicas: su comisión la custodia la
        // deuda del comercio vendedor y se le paga al cobrarla con la sala
        // cerrada (BingoRoomCreatorPayoutService).
        $custody = $this->network->currencySummary(
            \App\Models\Platform\PlatformReceivable::query()
                ->where('platform_receivables.creator_payee_user_id', $userId)
                ->where('platform_receivables.status', '!=', \App\Models\Platform\PlatformReceivable::STATUS_CANCELLED)
                ->whereNull('platform_receivables.creator_paid_at')
                ->where('platform_receivables.creator_amount', '>', 0),
            'creator_amount',
        );

        return [
            'activeRooms' => [
                'label' => 'Salas activas',
                'value' => $activeRooms,
                'icon' => 'fas fa-dice',
                'variant' => 'info',
                'href' => '/bingo-rooms',
            ],
            'cardsToday' => [
                'label' => 'Cartones vendidos hoy',
                'value' => $cardsToday,
                'icon' => 'fas fa-ticket-alt',
                'variant' => 'success',
            ],
            'salesThisMonth' => [
                'label' => 'Ventas del mes',
                'value' => 'US$ ' . number_format($salesUsd, 2, ',', '.'),
                'icon' => 'fas fa-shopping-cart',
                'variant' => 'primary',
                'description' => 'En tus salas · USD a la tasa de cada venta',
            ],
            'commission' => [
                'label' => 'Comisión de sala (mes)',
                'value' => $commission['amount'] ?? '—',
                'icon' => 'fas fa-percentage',
                'variant' => 'secondary',
                'description' => trim(implode(' · ', array_filter([
                    $commission['count'] > 0 ? 'Lo que ganas como organizador' : 'Sin comisión este mes',
                    $custody['amount'] ? "{$custody['amount']} por cobrar de otras redes" : null,
                ]))),
            ],
        ];
    }

    private function buildWallets(string $userId) : array
    {
        return $this->walletRepository->getActiveWalletsByUser($userId, ['id', 'currency_id', 'balance', 'bonus_balance'])
            ->load('currency:id,iso_code,symbol')
            ->map(fn ($wallet) => [
                'currency_code' => $wallet->currency?->iso_code ?? '—',
                'symbol' => $wallet->currency?->symbol ?? '',
                'balance' => (float) $wallet->balance,
                'bonus_balance' => (float) $wallet->bonus_balance,
            ])
            ->values()
            ->all();
    }

    /** Lotes vendidos (no reembolsados) en sus salas. */
    private function ownRoomLots(callable $ownRoomIds) : Builder
    {
        return $this->network->soldLots()->whereIn('bingo_card_lots.room_id', $ownRoomIds());
    }
}
