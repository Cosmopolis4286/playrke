<?php

namespace App\Http\Controllers\Roles\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\Platform\FinancialReconciliationService;
use App\Services\Platform\PlatformReceivableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Panel de Conciliación del dashboard superadmin:
 *  - Cuentas por cobrar: RKE de ventas fuera del sistema que cada comercio
 *    le debe a la plataforma (se acredita a la wallet al marcarse cobrado).
 *  - Alertas: movimientos de dinero que no pudieron completarse solos.
 */
class ReconciliationController extends Controller
{
    public function __construct(
        private readonly PlatformReceivableService $receivables,
        private readonly FinancialReconciliationService $reconciliation,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'receivables' => $this->receivables->pendingSummary(),
            'alerts'      => $this->reconciliation->openItems(),
            'alerts_open' => $this->reconciliation->openCount(),
        ]);
    }

    public function settleReceivables(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'debtor_user_id' => ['required', 'string'],
            'currency_id'    => ['required', 'string'],
        ]);

        $result = $this->receivables->settle($validated['debtor_user_id'], $validated['currency_id'], (string) Auth::id());

        if ($result['count'] === 0) {
            return response()->json(['message' => 'No hay deudas pendientes para ese comercio y moneda.'], 422);
        }

        return response()->json([
            'message' => "Cobro registrado: {$result['count']} venta(s), {$result['amount']} acreditado a la billetera de plataforma.",
            ...$result,
        ]);
    }

    public function resolveAlert(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $this->reconciliation->resolve($id, (string) Auth::id(), $validated['notes'] ?? null);

        return response()->json(['message' => 'Alerta marcada como resuelta.']);
    }
}
