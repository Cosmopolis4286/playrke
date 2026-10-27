<?php

namespace App\Services\Roles\Treasurer\Dashboard;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\DTOs\Roles\Treasurer\Dashboard\TreasurerDashboardContextDTO;
use App\Models\Platform\PlatformReceivable;
use App\Services\Menu\SidebarMenuService;
use App\Services\Platform\PlatformReceivableService;
use App\Services\Roles\Dashboard\NetworkDashboardService;
use App\Services\Roles\SuperAdmin\Dashboard\ExchangeRateSummaryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Contexto del dashboard de Treasurer (tesorería) — rol financiero: tasas
 * oficiales, deuda de los comercios con la plataforma, movimientos de
 * billetera pendientes y premios trabados. Antes no existía: el
 * DashboardController buscaba Roles/Treasurer/Dashboard/Index y la página
 * no cargaba.
 */
final class TreasurerDashboardContextService
{
    /** Horas sin actualizar a partir de las cuales una tasa se marca vieja. */
    private const STALE_RATE_HOURS = 24;

    public function __construct(
        private readonly SidebarMenuService $sidebarMenu,
        private readonly ExchangeRateSummaryService $exchangeRateSummary,
        private readonly PlatformReceivableService $receivables,
        private readonly NetworkDashboardService $network,
    ) {
    }

    public function dashboardContext() : TreasurerDashboardContextDTO
    {
        $user = Auth::user();
        $now = Carbon::now();

        $rates = $this->loadExchangeRates();
        $receivables = $this->receivables->pendingSummary();

        return new TreasurerDashboardContextDTO(
            userName: $user?->first_name ?? $user?->username ?? 'Tesorería',
            stats: $this->buildStats($rates, $receivables, $now, (bool) $user?->can('wallet.movements.view')),
            exchangeRates: $rates,
            receivables: array_slice($receivables, 0, 5),
            period: ucfirst($now->translatedFormat('F Y')),
        );
    }

    /**
     * @param array{rates: array<int, array<string, mixed>>} $rates
     * @param array<int, array<string, mixed>>               $receivables
     */
    private function buildStats(array $rates, array $receivables, Carbon $now, bool $canSeeMovements) : array
    {
        $pendingMovements = $this->sidebarMenu->pendingWalletMovementsCount();

        $staleRates = collect($rates['rates'] ?? [])
            ->filter(fn (array $r) => empty($r['updated_at'])
                || Carbon::parse($r['updated_at'])->lt($now->copy()->subHours(self::STALE_RATE_HOURS)))
            ->pluck('currency_code')
            ->values();

        // Deuda pendiente: moneda principal + "N más" (nunca se suman monedas).
        $debt = $this->network->currencySummary(
            PlatformReceivable::query()->where('platform_receivables.status', PlatformReceivable::STATUS_PENDING),
            'amount',
        );
        $debtors = collect($receivables)->pluck('debtor_user_id')->unique()->count();

        $stuckPrizes = BingoCardPrize::whereIn('prize_status', ['disputed', 'suspended'])->count();

        return [
            'pendingMovements' => array_filter([
                'label' => 'Movimientos por aprobar',
                'value' => $pendingMovements,
                'icon' => 'fas fa-hourglass-half',
                'variant' => $pendingMovements > 0 ? 'warning' : 'success',
                'description' => 'Recargas y retiros que esperan aprobación',
                'href' => $canSeeMovements ? '/wallet-movements' : null,
            ], fn ($v) => $v !== null),
            'receivables' => [
                'label' => 'Deuda de comercios',
                'value' => $debt['amount'] ?? '—',
                'icon' => 'fas fa-file-invoice-dollar',
                'variant' => $debt['count'] > 0 ? 'danger' : 'success',
                'description' => $debt['count'] > 0
                    ? "{$debt['count']} venta(s) sin liquidar · {$debtors} comercio(s)"
                    : 'Sin deuda pendiente con la plataforma',
            ],
            'staleRates' => [
                'label' => 'Tasas desactualizadas',
                'value' => $staleRates->count(),
                'icon' => 'fas fa-arrows-rotate',
                'variant' => $staleRates->isNotEmpty() ? 'warning' : 'success',
                'description' => $staleRates->isNotEmpty()
                    ? 'Más de ' . self::STALE_RATE_HOURS . ' h: ' . $staleRates->implode(', ')
                    : 'Todas actualizadas en las últimas ' . self::STALE_RATE_HOURS . ' h',
                'href' => '/exchange-rate',
            ],
            'stuckPrizes' => [
                'label' => 'Premios trabados',
                'value' => $stuckPrizes,
                'icon' => 'fas fa-triangle-exclamation',
                'variant' => $stuckPrizes > 0 ? 'danger' : 'success',
                'description' => 'En disputa o suspendidos',
            ],
        ];
    }

    /** @return array{rates: array<int, array<string, mixed>>} */
    private function loadExchangeRates() : array
    {
        try {
            return $this->exchangeRateSummary->getSummary();
        } catch (Throwable $e) {
            Log::warning('Dashboard tesorería: no se pudieron cargar las tasas', ['error' => $e->getMessage()]);

            return ['rates' => []];
        }
    }
}
