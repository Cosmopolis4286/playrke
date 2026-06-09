<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformWalletService;
use App\Services\Roles\SuperAdmin\Dashboard\SuperAdminDashboardContextService;
use App\Services\Roles\SuperAdmin\Rke\RkeSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador SuperAdmin
 *
 * Gestiona el panel de superadministración del sistema.
 * Proporciona vista extendida del dashboard con métricas de sistema.
 */
class SuperAdminController extends Controller
{
    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(
        protected SuperAdminDashboardContextService $dashboardContextService,
        protected RkeSummaryService $rkeSummaryService,
        protected PlatformWalletService $platformWallet,
    ) {}

    /**
     * Vista principal del dashboard de superadministrador
     */
    public function dashboard(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $context = $this->dashboardContextService->dashboardContext();
                $response = $context->toView();
                $response['title'] = 'SuperAdmin Dashboard';

                return Inertia::render('Roles/SuperAdmin/Dashboard/Index', $response);
            },
            'No se pudo cargar el dashboard de superadministrador.'
        );
    }

    /**
     * Resumen de comisiones RKE por sala (JSON para el dashboard superadmin)
     */
    public function rkeSummary(): JsonResponse
    {
        return response()->json($this->rkeSummaryService->getSummary());
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
