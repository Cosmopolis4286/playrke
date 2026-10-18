<?php

namespace App\Http\Controllers\Commissions;

use App\Http\Controllers\Controller;
use App\Services\Commissions\CommissionLedgerService;
use App\Traits\StreamsCsvExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Mis comisiones" (GET /commissions): histórico de comisiones propias con
 * cómo se cobró cada una (efectivo / billetera / custodiada) y su estado.
 * Autorización: gate commissions.viewOwn (CommissionLedgerPolicy).
 */
final class CommissionLedgerController extends Controller
{
    use StreamsCsvExport;

    public function __construct(
        private readonly CommissionLedgerService $service,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('commissions.viewOwn');

        return Inertia::render('Commissions/Index', [
            'title'   => 'Mis Comisiones',
            'ledger'  => $this->service->forUser(Auth::user(), $this->filters($request)),
            'filters' => $this->filters($request),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('commissions.viewOwn');

        return response()->json([
            'success' => true,
            'data'    => $this->service->forUser(Auth::user(), $this->filters($request)),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('commissions.viewOwn');

        $ledger = $this->service->forUser(Auth::user(), $this->filters($request));

        return $this->streamCsvExport(
            'mis-comisiones',
            ['Fecha', 'Tipo', 'Sala', 'Comprador', 'Cartones', 'Moneda', 'Venta', 'Comisión', 'Cómo se cobró', 'Estado'],
            $ledger['rows'],
            fn (array $row) => [
                $row['date'],
                $row['source_label'],
                $row['room'],
                $row['buyer'],
                $row['cards'],
                $row['currency'],
                $row['sale_amount'],
                $row['commission'],
                ['cash' => 'Efectivo', 'wallet' => 'Billetera', 'custody' => 'Custodiada (venta cruzada)'][$row['how']] ?? $row['how'],
                $row['status_label'],
            ],
        );
    }

    /** @return array{from: ?string, to: ?string, source: ?string, status: ?string} */
    private function filters(Request $request): array
    {
        $data = $request->validate([
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date'],
            'source' => ['nullable', 'in:seller,subsidiary,network,room'],
            'status' => ['nullable', 'in:paid,pending,refunded'],
        ]);

        return [
            'from'   => $data['from'] ?? null,
            'to'     => $data['to'] ?? null,
            'source' => $data['source'] ?? null,
            'status' => $data['status'] ?? null,
        ];
    }
}
