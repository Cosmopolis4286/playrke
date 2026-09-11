<?php

namespace App\Http\Controllers;

use App\Factories\Directories\Fiat\Brazil\PixKeyFactory;
use App\Factories\Directories\Fiat\Venezuela\PagoMovilFactory;
use App\Http\Requests\Directories\Fiat\Brazil\PixKeyRequest;
use App\Http\Requests\Directories\Fiat\Venezuela\PagoMovilRequest;
use App\Http\Support\PublicTokenResolver;
use App\Services\Banks\BrazilBankApiService;
use App\Services\Directory\DirectoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de directorios multi-país.
 *
 * Despacha operaciones de Pago Móvil (VE) y PIX (BR)
 * según el campo country_code del payload.
 *
 * Todas las acciones admiten target_user_id (query en GET, body en
 * POST/PATCH/DELETE) para que admin/superadmin gestionen el directorio de
 * otro usuario — validado y auditado en DirectoryAdminAccessService /
 * DirectoryAuditLogger; cualquier otro rol que lo envíe recibe 403.
 */
class DirectoryController extends Controller
{
    public function __construct(
        private PagoMovilFactory  $veFactory,
        private PixKeyFactory     $brFactory,
        private DirectoryService  $service,
        private PublicTokenResolver $tokenResolver,
        private BrazilBankApiService $brazilBankApi
    ) {}

    private function resolveToken(Request $request): ?string
    {
        return $this->tokenResolver->resolve($request);
    }

    private function resolveTargetUserId(Request $request): ?string
    {
        $target = $request->input('target_user_id') ?? $request->query('target_user_id');

        return $target ? (string) $target : null;
    }

    /* ------------------------------------------------------------------ */
    /* Inertia (carga inicial)                                             */
    /* ------------------------------------------------------------------ */

    public function index(Request $request): Response
    {
        return $this->handleServiceInertia(function () use ($request) {
            $countryCode = strtoupper($request->get('country', 'VE'));
            $response    = $this->service->context($this->resolveToken($request), $countryCode, $this->resolveTargetUserId($request));

            return Inertia::render('Directory/Index', $response);
        });
    }

    /* ------------------------------------------------------------------ */
    /* AJAX helpers                                                        */
    /* ------------------------------------------------------------------ */

    public function fetchList(Request $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $countryCode = strtoupper($request->get('country', 'VE'));
            return ['data' => $this->service->getAll($this->resolveToken($request), $countryCode, $this->resolveTargetUserId($request))];
        });
    }

    public function fetchContext(Request $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $countryCode = strtoupper($request->get('country', 'VE'));
            return ['data' => $this->service->getContextByCountry($this->resolveToken($request), $countryCode, $this->resolveTargetUserId($request))];
        });
    }

    /* ------------------------------------------------------------------ */
    /* Store — VE usa PagoMovilRequest, BR usa PixKeyRequest              */
    /* ------------------------------------------------------------------ */

    public function store(Request $request): JsonResponse
    {
        $countryCode = strtoupper($request->input('country_code', 'VE'));
        $targetUserId = $this->resolveTargetUserId($request);

        if ($countryCode === 'BR') {
            /** @var PixKeyRequest $validated */
            $pixRequest = app(PixKeyRequest::class);
            return $this->handleServiceJson(function () use ($pixRequest, $targetUserId) {
                $dto      = $this->brFactory->fromRequest($pixRequest->validated());
                $response = $this->service->createPixKey($dto, $targetUserId);
                return $response ? ['message' => '✅ Clave PIX registrada'] : false;
            });
        }

        /** @var PagoMovilRequest $veRequest */
        $veRequest = app(PagoMovilRequest::class);
        return $this->handleServiceJson(function () use ($veRequest, $targetUserId) {
            $dto      = $this->veFactory->fromRequest($veRequest->validated());
            $response = $this->service->create($dto, $targetUserId);
            return $response ? ['message' => '✅ Registro exitoso'] : false;
        });
    }

    /* ------------------------------------------------------------------ */
    /* Show                                                                */
    /* ------------------------------------------------------------------ */

    public function show(Request $request, string $id): JsonResponse
    {
        $countryCode = strtoupper($request->get('country', 'VE'));
        $targetUserId = $this->resolveTargetUserId($request);

        return $this->handleServiceJson(function () use ($id, $countryCode, $targetUserId) {
            $data = $countryCode === 'BR'
                ? $this->service->showPixKey($id, $targetUserId)
                : $this->service->show($id, $targetUserId);

            return ['data' => $data];
        });
    }

    /* ------------------------------------------------------------------ */
    /* Update — VE usa PagoMovilRequest, BR usa PixKeyRequest             */
    /* ------------------------------------------------------------------ */

    public function update(Request $request, string $id): JsonResponse
    {
        $countryCode = strtoupper($request->input('country_code', 'VE'));
        $targetUserId = $this->resolveTargetUserId($request);

        if ($countryCode === 'BR') {
            $pixRequest = app(PixKeyRequest::class);
            return $this->handleServiceJson(function () use ($pixRequest, $id, $targetUserId) {
                $dto      = $this->brFactory->fromRequest($pixRequest->validated(), $id);
                $response = $this->service->updatePixKey($dto, $targetUserId);
                return $response ? ['message' => '✅ Clave PIX actualizada'] : false;
            });
        }

        $veRequest = app(PagoMovilRequest::class);
        return $this->handleServiceJson(function () use ($veRequest, $id, $targetUserId) {
            $dto      = $this->veFactory->fromRequest($veRequest->validated(), $id);
            $response = $this->service->update($dto, $targetUserId);
            return $response ? ['message' => '✅ Actualización exitosa'] : false;
        });
    }

    /* ------------------------------------------------------------------ */
    /* Delete / ToggleStatus                                               */
    /* ------------------------------------------------------------------ */

    public function delete(Request $request, string $id): JsonResponse
    {
        $countryCode = strtoupper($request->get('country', 'VE'));
        $targetUserId = $this->resolveTargetUserId($request);

        return $this->handleServiceJson(function () use ($id, $countryCode, $targetUserId) {
            if ($countryCode === 'BR') {
                $response = $this->service->deletePixKey($id, $targetUserId);
                return $response ? ['message' => '✅ Clave PIX eliminada correctamente'] : false;
            }

            $response = $this->service->delete($id, $targetUserId);
            return $response ? ['message' => '✅ Directorio eliminado correctamente'] : false;
        });
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $countryCode = strtoupper($request->get('country', 'VE'));
        $targetUserId = $this->resolveTargetUserId($request);

        return $this->handleServiceJson(function () use ($id, $countryCode, $targetUserId) {
            if ($countryCode === 'BR') {
                $this->service->togglePixKeyStatus($id, $targetUserId);
                return ['message' => 'Estado actualizado correctamente'];
            }

            return ['message' => 'Estado actualizado correctamente', 'data' => $this->service->toggleStatus($id, $targetUserId)];
        });
    }

    /* ------------------------------------------------------------------ */
    /* Brazil banks sync                                                   */
    /* ------------------------------------------------------------------ */

    public function fetchBrazilBanks(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->brazilBankApi->fetchAndSync()]
        );
    }
}
