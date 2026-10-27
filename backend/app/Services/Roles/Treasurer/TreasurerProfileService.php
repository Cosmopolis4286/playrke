<?php

namespace App\Services\Roles\Treasurer;

use App\Domains\Wallet\Models\WalletMovement;
use App\Models\MovementType;
use App\Models\Roles\Treasurer;
use App\Services\Roles\AdminProfileAccessService;
use App\Services\Roles\RoleProfileEnrichmentService;
use App\Services\Roles\RoleProfileInsightsService as Insights;
use App\Services\Security\UserContextService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Autogestión de la ficha propia de un Treasurer ("Mi Tesorería") — distinto
 * de AdminTreasurerService (gestión de terceros). Mismo patrón que el resto de
 * fichas de rol: por defecto resuelve el Treasurer del usuario autenticado;
 * admin/superadmin pueden inspeccionar uno ajeno vía $targetUserId.
 *
 * El tesorero no vende: su desempeño es la revisión de movimientos de
 * billetera (depósitos, retiros…) — wallet_movements.processed_by es quien
 * los aprobó/rechazó y assigned_to_user_id quien los tiene tomados ahora.
 */
final class TreasurerProfileService
{
    private const TREND_DAYS = 14;

    public function __construct(
        private readonly UserContextService $userContext,
        private readonly AdminProfileAccessService $adminAccess,
        private readonly RoleProfileEnrichmentService $enrichment,
        private readonly Insights $insights,
    ) {
    }

    public function showOwn(?string $targetUserId = null) : array
    {
        $treasurer = $this->ownTreasurerOrFail($targetUserId);
        $treasurer->load(['country:id,name', 'user:id,username,email']);

        $userId = (string) $treasurer->user_id;
        $treasury = $this->treasury($userId);

        return [
            'role' => 'treasurer',
            'id' => $treasurer->hashed_id,
            'name' => $treasurer->name,
            'phone' => $treasurer->phone,
            'status' => $treasurer->status,
            'country' => $treasurer->country?->name,
            'user' => ['username' => $treasurer->user->username, 'email' => $treasurer->user->email],
            'commission' => (float) $treasurer->commission,
            'currencies' => $treasurer->currenciesActive()->pluck('iso_code')->all(),
            'games' => $treasurer->gamesActive()->pluck('name')->all(),

            'hierarchy' => [
                'chain' => [['level' => 'treasurer', 'name' => $treasurer->name, 'rate' => (float) $treasurer->commission, 'status' => $treasurer->status, 'is_self' => true]],
            ],

            'treasury' => $treasury,
            'alerts' => $this->alerts($treasurer, $treasury),
            'security' => $this->insights->security($userId),
            'wallets' => $this->enrichment->wallets($treasurer->user),
            'recent_movements' => $this->enrichment->recentMovements($treasurer->user),
            'account_meta' => $this->enrichment->accountMeta($userId),
        ];
    }

    /** @param array{name?: string, phone?: string} $data */
    public function updateOwn(array $data, ?string $targetUserId = null) : void
    {
        $treasurer = $this->ownTreasurerOrFail($targetUserId);
        $treasurer->update(array_intersect_key($data, array_flip(['name', 'phone'])));
    }

    private function alerts(Treasurer $treasurer, array $treasury) : array
    {
        $alerts = [];

        if ($treasurer->status !== 'ACTIVE') {
            $alerts[] = Insights::alert(
                'danger',
                'fa-ban',
                'Tesorero no activo',
                'Tu cuenta está ' . strtolower((string) $treasurer->status) . ': no puedes procesar movimientos. Contacta a soporte.',
            );
        }

        if ($treasury['queue']['assigned_me'] > 0) {
            $alerts[] = Insights::alert(
                'warning',
                'fa-user-clock',
                'Tienes casos tomados',
                "{$treasury['queue']['assigned_me']} movimiento(s) asignados a ti siguen pendientes de resolver.",
                'Revisar',
                '/wallet-movements',
            );
        }

        if ($treasury['queue']['unassigned'] > 0) {
            $oldest = $treasury['queue']['oldest_at'] ? ' El más antiguo espera desde ' . Carbon::parse($treasury['queue']['oldest_at'])->locale('es')->diffForHumans() . '.' : '';
            $alerts[] = Insights::alert(
                'info',
                'fa-inbox',
                'Movimientos por revisar',
                "{$treasury['queue']['unassigned']} movimiento(s) sin asignar en la cola.{$oldest}",
                'Ir a la cola',
                '/wallet-movements',
            );
        }

        return $alerts;
    }

    private function ownTreasurerOrFail(?string $targetUserId = null) : Treasurer
    {
        $access = $this->adminAccess->resolve($this->userContext->resolveUserId(), $targetUserId);

        $treasurer = Treasurer::where('user_id', $access['owner_user_id'])->first();

        abort_if(! $treasurer, 404, 'No se encontró un tesorero asociado a este usuario.');

        return $treasurer;
    }

    /**
     * Desempeño de revisión: este mes vs. mismo tramo del mes anterior, cola
     * actual, casos tomados, tendencia diaria y tipos más revisados.
     */
    private function treasury(string $userId) : array
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $prevStart = $monthStart->copy()->subMonthNoOverflow();
        $prevEnd = $now->copy()->subMonthNoOverflow();

        $processed = fn (Carbon $from, Carbon $to) => WalletMovement::where('processed_by', $userId)
            ->whereBetween('processed_at', [$from, $to])
            ->groupBy('status')
            ->pluck(DB::raw('COUNT(*) as total'), 'status')
            ->map(fn ($n) => (int) $n);

        $current = $processed($monthStart, $now);
        $previous = $processed($prevStart, $prevEnd);

        $kpi = function(int $value, int $prev) : array {
            return ['value' => $value, 'previous' => $prev, 'change_pct' => $prev > 0 ? (int) round(($value - $prev) / $prev * 100) : null];
        };

        $approved = (int) ($current[WalletMovement::STATUS_COMPLETED] ?? 0);
        $rejected = (int) ($current[WalletMovement::STATUS_REJECTED] ?? 0);

        $pending = WalletMovement::where('status', WalletMovement::STATUS_PENDING);

        $trendStart = $now->copy()->subDays(self::TREND_DAYS - 1)->startOfDay();
        $byDay = WalletMovement::where('processed_by', $userId)
            ->where('processed_at', '>=', $trendStart)
            ->groupBy(DB::raw('DATE(processed_at)'))
            ->pluck(DB::raw('COUNT(*) as total'), DB::raw('DATE(processed_at) as day'));

        $trend = [];
        for ($i = 0; $i < self::TREND_DAYS; $i++) {
            $date = $trendStart->copy()->addDays($i)->toDateString();
            $trend[] = ['date' => $date, 'count' => (int) ($byDay[$date] ?? 0)];
        }

        $topTypes = WalletMovement::where('processed_by', $userId)
            ->where('processed_at', '>=', $monthStart)
            ->groupBy('type')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(5)
            ->pluck(DB::raw('COUNT(*) as total'), 'type');
        $labels = MovementType::whereIn('code', $topTypes->keys())->pluck('label', 'code');

        $lifetime = WalletMovement::where('processed_by', $userId)
            ->selectRaw('COUNT(*) as total, MIN(processed_at) as first_at, MAX(processed_at) as last_at')
            ->first();

        return [
            'period_label' => ucfirst($now->copy()->locale('es')->translatedFormat('F Y')),
            'kpis' => [
                'processed' => $kpi($current->sum(), $previous->sum()),
                'approved' => $kpi($approved, (int) ($previous[WalletMovement::STATUS_COMPLETED] ?? 0)),
                'rejected' => $kpi($rejected, (int) ($previous[WalletMovement::STATUS_REJECTED] ?? 0)),
            ],
            'approval_rate' => ($approved + $rejected) > 0 ? (int) round($approved / ($approved + $rejected) * 100) : null,
            'queue' => [
                'pending' => (clone $pending)->count(),
                'unassigned' => (clone $pending)->whereNull('assigned_to_user_id')->count(),
                'assigned_me' => (clone $pending)->where('assigned_to_user_id', $userId)->count(),
                'oldest_at' => (clone $pending)->min('created_at')
                    ? Carbon::parse((clone $pending)->min('created_at'))->toIso8601String()
                    : null,
            ],
            'trend' => $trend,
            'top_types' => $topTypes->map(fn ($n, $type) => [
                'type' => $type,
                'label' => $labels[$type] ?? $type,
                'count' => (int) $n,
            ])->values()->all(),
            'lifetime' => [
                'processed' => (int) ($lifetime->total ?? 0),
                'first_at' => $lifetime?->first_at ? Carbon::parse($lifetime->first_at)->toIso8601String() : null,
                'last_at' => $lifetime?->last_at ? Carbon::parse($lifetime->last_at)->toIso8601String() : null,
            ],
        ];
    }
}
