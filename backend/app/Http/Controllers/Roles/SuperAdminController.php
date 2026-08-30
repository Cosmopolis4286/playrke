<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformWalletService;
use App\Services\Roles\SuperAdmin\Rke\RkeSummaryService;
use App\Services\Roles\SuperAdmin\Dashboard\CommerceHierarchyService;
use App\Services\Roles\SuperAdmin\Dashboard\SuperAdminDashboardContextService;
use App\Services\Roles\SuperAdmin\Dashboard\TriviaSummaryService;
use App\Services\Roles\SuperAdmin\Dashboard\ExchangeRateSummaryService;
use App\Services\Roles\SuperAdmin\Dashboard\EconomySummaryService;
use App\Services\Roles\SuperAdmin\Dashboard\PlayerActivitySummaryService;
use App\Services\Roles\Seller\SellerRankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador SuperAdmin
 *
 * Gestiona los endpoints de billetera de plataforma y comisiones RKE.
 * El dashboard de superadministrador se sirve desde DashboardController
 * (ruta única `/dashboard`, resuelta por rol).
 */
class SuperAdminController extends Controller
{
    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(
        protected RkeSummaryService $rkeSummaryService,
        protected PlatformWalletService $platformWallet,
        protected CommerceHierarchyService $commerceHierarchy,
        protected TriviaSummaryService $triviaSummary,
        protected ExchangeRateSummaryService $exchangeRateSummary,
        protected SuperAdminDashboardContextService $dashboardContext,
        protected EconomySummaryService $economySummary,
        protected PlayerActivitySummaryService $playerActivitySummary,
        protected SellerRankingService $sellerRanking,
    ) {}

    /**
     * Resumen de comisiones RKE por sala (JSON para el dashboard superadmin)
     */
    /**
     * Resumen económico consolidado cross-juego: dinero ingresado por
     * juego/moneda/comercializadora (JSON para el dashboard superadmin)
     */
    public function economySummary(): JsonResponse
    {
        return response()->json($this->economySummary->getSummary());
    }

    /**
     * Jugadores activos por ventana + clientes recurrentes por vendedor
     * (JSON para el dashboard superadmin)
     */
    public function playerActivitySummary(): JsonResponse
    {
        return response()->json($this->playerActivitySummary->getSummary());
    }

    /**
     * Ranking mensual de vendedores por comisión generada (JSON para el
     * dashboard superadmin)
     */
    public function sellerRanking(): JsonResponse
    {
        return response()->json($this->sellerRanking->getMonthlyRanking());
    }

    public function rkeSummary(): JsonResponse
    {
        return response()->json($this->rkeSummaryService->getSummary());
    }

    /**
     * Jerarquía Comercializadora → Agencia → Vendedor (JSON para el dashboard superadmin)
     */
    public function commerceHierarchy(): JsonResponse
    {
        return response()->json($this->commerceHierarchy->getHierarchy());
    }

    /**
     * Resumen de actividad Trivia por sala (JSON para el dashboard superadmin)
     */
    public function triviaSummary(): JsonResponse
    {
        return response()->json($this->triviaSummary->getSummary());
    }

    /**
     * Tasas de cambio activas agrupadas por moneda (JSON para el dashboard superadmin)
     */
    public function exchangeRateSummary(): JsonResponse
    {
        return response()->json($this->exchangeRateSummary->getSummary());
    }

    /**
     * Salud del sistema (JSON para el widget "Salud del sistema" del
     * dashboard superadmin) — endpoint dedicado para el polling del
     * frontend, sin repintar todo dashboardContext() en cada refresco.
     * Cacheado 20s server-side (ver systemHealthForPolling()), así que
     * varios admins con el dashboard abierto no duplican el cálculo.
     */
    public function systemHealth(): JsonResponse
    {
        return response()->json([
            'success' => true,
            ...$this->dashboardContext->systemHealthForPolling(),
        ]);
    }

    /**
     * Saldos de la billetera de plataforma (todas las monedas)
     */
    public function platformWallets(): JsonResponse
    {
        return response()->json($this->platformWallet->getWallets());
    }

    /**
     * Movimientos paginados de la billetera de plataforma para una moneda
     */
    public function platformMovements(Request $request): JsonResponse
    {
        $currencyId = $request->query('currency_id', '');
        $perPage    = (int) $request->query('per_page', 20);

        if (! $currencyId) {
            return response()->json(['data' => [], 'total' => 0]);
        }

        $paginator = $this->platformWallet->getMovements($currencyId, $perPage);

        return response()->json($paginator);
    }

    /**
     * Deposita fondos manualmente en la wallet de plataforma
     */
    public function platformDeposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency_id' => ['required', 'string'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->platformWallet->deposit(
                $validated['currency_id'],
                (float) $validated['amount'],
                $validated['description'] ?? 'Depósito manual'
            );

            return response()->json(['message' => 'Depósito registrado correctamente.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'No se pudo registrar el depósito.'], 500);
        }
    }

    /**
     * Retira fondos manualmente de la wallet de plataforma
     */
    public function platformWithdraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency_id' => ['required', 'string'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->platformWallet->withdraw(
                $validated['currency_id'],
                (float) $validated['amount'],
                $validated['description'] ?? 'Retiro manual'
            );

            return response()->json(['message' => 'Retiro registrado correctamente.']);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'No se pudo registrar el retiro.'], 500);
        }
    }
}
