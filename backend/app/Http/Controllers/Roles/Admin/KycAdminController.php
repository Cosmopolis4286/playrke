<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Policies\Roles\Admin\KycPolicy;
use App\Services\Roles\Admin\AdminKycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Cola de revisión de documentos KYC enviados por jugadores.
 *
 * La lógica de dominio (aprobar/rechazar, subir cambia kyc_level/kyc_status
 * del User) vive en App\Services\KycService — este controlador solo cubre
 * lo que faltaba: listado/detalle/streaming de imágenes para el panel admin.
 *
 * KycPolicy es una capa de defensa independiente del middleware
 * `multiPermission:kyc.view,kyc.manage` de la ruta (mismo criterio que
 * JobsCenterController) — si el middleware de ruta cambia algún día, la
 * autorización real sigue exigida aquí.
 */
final class KycAdminController extends Controller
{
    private const ALLOWED_IMAGE_TYPES = ['front', 'back', 'selfie'];

    public function __construct(
        private readonly AdminKycService $service,
    ) {
    }

    public function approve(KycDocument $document) : JsonResponse
    {
        $this->authorize('manage', KycPolicy::class);

        return $this->handleServiceJson(function() use ($document) {
            $this->service->approve($document);

            return ['message' => 'Documento aprobado. Verificación del jugador actualizada.'];
        });
    }

    /**
     * Exporta a CSV todos los documentos que coincidan con los filtros
     * actuales (sin límite de paginación) — mismo patrón que
     * AdminPlayerController::export().
     */
    public function export(Request $request) : StreamedResponse
    {
        $this->authorize('viewAny', KycPolicy::class);

        $filters = $request->only(['status', 'document_type', 'search']);

        return response()->streamDownload(function() use ($filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Jugador', 'Username', 'Email', 'Tipo documento', 'Número documento', 'Estado', 'Enviado', 'Revisado']);

            foreach ($this->service->exportForCsv($filters) as $document) {
                fputcsv($handle, [
                    trim("{$document->user?->first_name} {$document->user?->last_name}"),
                    $document->user?->username,
                    $document->user?->email,
                    $document->document_type,
                    $document->document_number,
                    $document->status,
                    $document->created_at?->format('Y-m-d H:i:s'),
                    $document->reviewed_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 'kyc-documentos-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function fetchList(Request $request) : JsonResponse
    {
        $this->authorize('viewAny', KycPolicy::class);

        return $this->handleServiceJson(function() use ($request) {
            $filters = $request->only(['status', 'document_type', 'search']);
            $page = (int) $request->get('page', 1);

            return [
                'data' => $this->service->fetchList($filters, $page),
                'stats' => $this->service->stats(),
            ];
        });
    }

    /**
     * Stream protegido de una de las 3 imágenes del documento — los archivos
     * viven fuera del document root (storage/app/private/kyc/{userId}/...,
     * ver KycService::storeFile()), así que no hay URL pública que servir;
     * cada request pasa por este endpoint autenticado (mismo patrón que la
     * ruta wallet/receipt/{filename}, ver routes/modules/wallets.php).
     */
    public function image(KycDocument $document, string $type) : BinaryFileResponse
    {
        $this->authorize('view', KycPolicy::class);

        abort_unless(in_array($type, self::ALLOWED_IMAGE_TYPES, true), 404);

        $column = "{$type}_path";
        $relativePath = $document->{$column};

        abort_unless($relativePath, 404);

        $path = storage_path("app/private/{$relativePath}");

        abort_unless(is_file($path), 404);

        return response()->file($path);
    }

    public function index(Request $request) : Response
    {
        $this->authorize('viewAny', KycPolicy::class);

        return $this->handleServiceInertia(function() use ($request) {
            $filters = $request->only(['status', 'document_type', 'search']);

            return Inertia::render('Roles/Admin/Kyc/Index', [
                'documents' => $this->service->fetchList($filters),
                'stats' => $this->service->stats(),
            ]);
        });
    }

    public function reject(KycDocument $document, Request $request) : JsonResponse
    {
        $this->authorize('manage', KycPolicy::class);

        $request->validate(['reason' => 'required|string|max:500']);

        return $this->handleServiceJson(function() use ($document, $request) {
            $this->service->reject($document, $request->input('reason'));

            return ['message' => 'Documento rechazado.'];
        });
    }

    public function show(KycDocument $document) : JsonResponse
    {
        $this->authorize('view', KycPolicy::class);

        return $this->handleServiceJson(
            fn () => ['data' => $this->service->show($document)],
        );
    }
}
