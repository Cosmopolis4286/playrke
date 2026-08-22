<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Services\Roles\Admin\AdminKycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Cola de revisión de documentos KYC enviados por jugadores.
 *
 * La lógica de dominio (aprobar/rechazar, subir cambia kyc_level/kyc_status
 * del User) vive en App\Services\KycService — este controlador solo cubre
 * lo que faltaba: listado/detalle/streaming de imágenes para el panel admin.
 */
final class KycAdminController extends Controller
{
    private const ALLOWED_IMAGE_TYPES = ['front', 'back', 'selfie'];

    public function __construct(
        private readonly AdminKycService $service
    ) {}

    public function index(Request $request): Response
    {
        return $this->handleServiceInertia(function () use ($request) {
            $filters = $request->only(['status']);

            return Inertia::render('Roles/Admin/Kyc/Index', [
                'documents' => $this->service->fetchList($filters),
            ]);
        });
    }

    public function fetchList(Request $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $filters = $request->only(['status']);
            $page    = (int) $request->get('page', 1);

            return ['data' => $this->service->fetchList($filters, $page)];
        });
    }

    public function show(KycDocument $document): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->show($document)]
        );
    }

    /**
     * Stream protegido de una de las 3 imágenes del documento — los archivos
     * viven fuera del document root (storage/app/private/kyc/{userId}/...,
     * ver KycService::storeFile()), así que no hay URL pública que servir;
     * cada request pasa por este endpoint autenticado (mismo patrón que la
     * ruta wallet/receipt/{filename}, ver routes/modules/wallets.php).
     */
    public function image(KycDocument $document, string $type): BinaryFileResponse
    {
        abort_unless(in_array($type, self::ALLOWED_IMAGE_TYPES, true), 404);

        $column = "{$type}_path";
        $relativePath = $document->{$column};

        abort_unless($relativePath, 404);

        $path = storage_path("app/private/{$relativePath}");

        abort_unless(is_file($path), 404);

        return response()->file($path);
    }

    public function approve(KycDocument $document): JsonResponse
    {
        return $this->handleServiceJson(function () use ($document) {
            $this->service->approve($document);

            return ['message' => 'Documento aprobado. Verificación del jugador actualizada.'];
        });
    }

    public function reject(KycDocument $document, Request $request): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        return $this->handleServiceJson(function () use ($document, $request) {
            $this->service->reject($document, $request->input('reason'));

            return ['message' => 'Documento rechazado.'];
        });
    }
}
