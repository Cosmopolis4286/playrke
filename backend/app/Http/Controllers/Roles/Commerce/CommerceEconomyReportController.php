<?php

namespace App\Http\Controllers\Roles\Commerce;

use App\Http\Controllers\Controller;
use App\Services\Roles\Commerce\Report\CommerceEconomySummaryService;
use App\Traits\StreamsCsvExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reporte financiero consolidado de la red propia de un Commerce — mismo
 * criterio de "opera siempre sobre el registro propio" que
 * CommerceProfileController, sin {id} de URL: el commerceUserId siempre es
 * Auth::id(), nunca un parámetro de ruta.
 */
final class CommerceEconomyReportController extends Controller
{
    use StreamsCsvExport;

    public function __construct(
        private readonly CommerceEconomySummaryService $service,
    ) {
    }

    public function exportBySeller(Request $request) : StreamedResponse
    {
        $summary = $this->service->getSummary(
            commerceUserId: (string) Auth::id(),
            startDate: $request->query('start_date'),
            endDate: $request->query('end_date'),
        );

        return $this->streamCsvExport(
            'resumen-financiero-por-vendedor',
            ['Vendedor', 'Cartones', 'Total Vendido', 'Comisión Generada'],
            $summary['by_seller'],
            fn (array $row) => [
                $row['seller_name'],
                $row['total_cards'],
                $row['total_sales'],
                $row['total_commission'],
            ],
        );
    }

    public function fetch(Request $request) : JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getSummary(
                commerceUserId: (string) Auth::id(),
                startDate: $request->query('start_date'),
                endDate: $request->query('end_date'),
            ),
        ]);
    }

    public function index(Request $request) : Response
    {
        return Inertia::render('Roles/Commerce/Report/EconomySummary', [
            'title' => 'Resumen Financiero',
            'summary' => $this->service->getSummary(
                commerceUserId: (string) Auth::id(),
                startDate: $request->query('start_date'),
                endDate: $request->query('end_date'),
            ),
            'filters' => [
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
            ],
        ]);
    }
}
