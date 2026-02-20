<?php

namespace App\Http\Controllers;

use App\Factories\Directories\Fiat\Venezuela\PagoMovilFactory;
use App\Http\Requests\Directories\Fiat\Venezuela\PagoMovilRequest;
use App\Http\Support\PublicTokenResolver;
use App\Services\Banks\BrazilBankApiService;
use App\Services\Directory\DirectoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de directorios.
 *
 * Recibe las peticiones HTTP y delega en el DirectoryService.
 * Maneja respuestas Inertia y JSON, con manejo centralizado de errores.
 */
class DirectoryController extends Controller
{
    public function __construct(
        private PagoMovilFactory $factory,
        private DirectoryService $service,
        private PublicTokenResolver $tokenResolver,
        private BrazilBankApiService $brazilBankApi
    ) {}

    /**
     * Obtiene el token público desde el request (si existe).
     *
     * @param Request $request
     * @return string|null
     */
    private function resolveToken(Request $request): ?string
    {
        return $this->tokenResolver->resolve($request);
    }

    /**
     * Muestra los directorios usando Inertia (carga inicial con VE por defecto).
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->handleServiceInertia(
            function () use ($request) {
                $countryCode = strtoupper($request->get('country', 'VE'));
                $response = $this->service->context($this->resolveToken($request), $countryCode);

                return Inertia::render('Directory/Index', $response);
            },
            'No se pudo cargar la lista de directorios.'
        );
    }

    /**
     * Endpoint para obtener los directorios según el país.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchList(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($request) {
                $countryCode = strtoupper($request->get('country', 'VE'));

                $data = $this->service->getAll($this->resolveToken($request), $countryCode);

                return ['data' => $data];
            },
            'No se pudo cargar la lista de directorios.'
        );
    }

    /**
     * Endpoint AJAX para obtener bancos + documentTypes + directorios según el país.
     * Usado al cambiar de tab en el frontend.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchContext(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($request) {
                $countryCode = strtoupper($request->get('country', 'VE'));

                $data = $this->service->getContextByCountry($this->resolveToken($request), $countryCode);

                return ['data' => $data];
            },
            'No se pudo cargar el contexto del directorio.'
        );
    }

    /**
     * Crear/registrar un directorio.
     *
     * @param PagoMovilRequest $request
     * @return JsonResponse
     */
    public function store(PagoMovilRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($request) {
                $dto = $this->factory->fromRequest($request->validated());

                $response = $this->service->create($dto);

                if ($response === true) {
                    return ['message' => '✅ Registro exitoso'];
                }

                // Retorna false para que el helper considere error
                return false;
            },
            'Hubo un error al registrar un directorio.'
        );
    }

    /**
     * Mostrar detalle de un directorio por su ID.
     *
     * @param string $id Identificador del directorio.
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->show($id)],
            'No se pudo cargar la información del directorio.'
        );
    }

    /**
     * Actualizar un directorio existente.
     *
     * @param PagoMovilRequest $request
     * @param string $id Identificador del directorio.
     * @return JsonResponse
     */
    public function update(PagoMovilRequest $request, string $id): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($request, $id) {
                $dto = $this->factory->fromRequest($request->validated(), $id);

                $response = $this->service->update($dto);

                if ($response === true) {
                    return ['message' => '✅ Actualización exitosa'];
                }

                return false;
            },
            'No se pudo actualizar el directorio.'
        );
    }

    /**
     * Eliminar un directorio (soft delete).
     *
     * @param string $id Identificador del directorio.
     * @return JsonResponse
     */
    public function delete(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($id) {
                $response = $this->service->delete($id);

                if ($response === true) {
                    return ['message' => '✅ Directorio eliminado correctamente'];
                }

                return false;
            },
            'No se pudo eliminar el directorio.'
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE).
     *
     * @param string $id
     * @return JsonResponse
     */
    public function toggleStatus(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'message' => 'Estado actualizado correctamente',
                'data'    => $this->service->toggleStatus($id),
            ],
            'No se pudo cambiar el estado del directorio.'
        );
    }

    /**
     * Obtiene la lista de bancos de Brasil desde la BrasilAPI,
     * sincronizando los nuevos en la BD.
     *
     * @return JsonResponse
     */
    public function fetchBrazilBanks(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->brazilBankApi->fetchAndSync()],
            'No se pudo obtener la lista de bancos de Brasil.'
        );
    }
}
