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

    /* ------------------------------------------------------------------ */
    /* Inertia (carga inicial)                                             */
    /* ------------------------------------------------------------------ */

    public function index(Request $request): Response
    {
        return $this->handleServiceInertia(function () use ($request) {
            $countryCode = strtoupper($request->get('country', 'VE'));
            $response    = $this->service->context($this->resolveToken($request), $countryCode);

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
            return ['data' => $this->service->getAll($this->resolveToken($request), $countryCode)];
        });
    }

    public function fetchContext(Request $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $countryCode = strtoupper($request->get('country', 'VE'));
            return ['data' => $this->service->getContextByCountry($this->resolveToken($request), $countryCode)];
        });
    }

    /* ------------------------------------------------------------------ */
    /* Store — VE usa PagoMovilRequest, BR usa PixKeyRequest              */
    /* ------------------------------------------------------------------ */

    public function store(Request $request): JsonResponse
    {
        $countryCode = strtoupper($request->input('country_code', 'VE'));

        if ($countryCode === 'BR') {
            /** @var PixKeyRequest $validated */
            $pixRequest = app(PixKeyRequest::class);
            return $this->handleServiceJson(function () use ($pixRequest) {
                $dto      = $this->brFactory->fromRequest($pixRequest->validated());
                $response = $this->service->createPixKey($dto);
                return $response ? ['message' => '✅ Clave PIX registrada'] : false;
            });
        }

        /** @var PagoMovilRequest $veRequest */
        $veRequest = app(PagoMovilRequest::class);
        return $this->handleServiceJson(function () use ($veRequest) {
            $dto      = $this->veFactory->fromRequest($veRequest->validated());
            $response = $this->service->create($dto);
            return $response ? ['message' => '✅ Registro exitoso'] : false;
        });
    }

    /* ------------------------------------------------------------------ */
    /* Show                                                                */
    /* ------------------------------------------------------------------ */

    public function show(Request $request, string $id): JsonResponse
    {
        $countryCode = strtoupper($request->get('country', 'VE'));

        return $this->handleServiceJson(function () use ($id, $countryCode) {
            $data = $countryCode === 'BR'
                ? $this->service->showPixKey($id)
                : $this->service->show($id);

            return ['data' => $data];
        });
    }

    /* ------------------------------------------------------------------ */
    /* Update — VE usa PagoMovilRequest, BR usa PixKeyRequest             */
    /* ------------------------------------------------------------------ */

    public function update(Request $request, string $id): JsonResponse
    {
        $countryCode = strtoupper($request->input('country_code', 'VE'));

        if ($countryCode === 'BR') {
            $pixRequest = app(PixKeyRequest::class);
            return $this->handleServiceJson(function () use ($pixRequest, $id) {
                $dto      = $this->brFactory->fromRequest($pixRequest->validated(), $id);
                $response = $this->service->updatePixKey($dto);
                return $response ? ['message' => '✅ Clave PIX actualizada'] : false;
            });
        }

        $veRequest = app(PagoMovilRequest::class);
        return $this->handleServiceJson(function () use ($veRequest, $id) {
            $dto      = $this->veFactory->fromRequest($veRequest->validated(), $id);
            $response = $this->service->update($dto);
            return $response ? ['message' => '✅ Actualización exitosa'] : false;
        });
    }

    /* ------------------------------------------------------------------ */
    /* Delete / ToggleStatus                                               */
    /* ------------------------------------------------------------------ */

    public function delete(Request $request, string $id): JsonResponse
    {
        $countryCode = strtoupper($request->get('country', 'VE'));

        return $this->handleServiceJson(function () use ($id, $countryCode) {
            if ($countryCode === 'BR') {
                $response = $this->service->deletePixKey($id);
                return $response ? ['message' => '✅ Clave PIX eliminada correctamente'] : false;
            }

            $response = $this->service->delete($id);
            return $response ? ['message' => '✅ Directorio eliminado correctamente'] : false;
        });
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $countryCode = strtoupper($request->get('country', 'VE'));

        return $this->handleServiceJson(function () use ($id, $countryCode) {
            if ($countryCode === 'BR') {
                $this->service->togglePixKeyStatus($id);
                return ['message' => 'Estado actualizado correctamente'];
            }

            return ['message' => 'Estado actualizado correctamente', 'data' => $this->service->toggleStatus($id)];
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
