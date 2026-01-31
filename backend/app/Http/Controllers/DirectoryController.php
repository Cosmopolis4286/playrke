<?php

namespace App\Http\Controllers;

use App\Factories\Directory\DirectoryFactory;
use App\Http\Requests\Directory\DirectoryRequest;
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
        private DirectoryFactory $factory,
        private DirectoryService $service,
    ) {}

    /**
     * Muestra los directorios usando Inertia.
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $response = $this->service->context();

                return Inertia::render('Directory/Index', $response);
            },
            'No se pudo cargar la lista de directorios.'
        );
    }

    /**
     * Endpoint para obtener los directorios según el pais.
     *
     * @return JsonResponse
     */
    public function listFiltered(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($request) {

                $data = $this->service->getAll();

                return ['data' => $data];
            },
            'No se pudo cargar la lista de directorios.'
        );
    }

    /**
     * Crear/registrar un directorio.
     *
     * @param DirectoryRequest $request
     * @return JsonResponse
     */
    public function store(DirectoryRequest $request): JsonResponse
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
     * @param DirectoryRequest $request
     * @param string $id Identificador del directorio.
     * @return JsonResponse
     */
    public function update(DirectoryRequest $request, string $id): JsonResponse
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
     * Alternar el estado (activo/inactivo) de un directorio.
     *
     * @param string $id Identificador del directorio.
     * @return JsonResponse
     */
    public function toggleStatus(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($id) {
                $this->service->toggleStatus($id);

                return ['message' => '✅ Estado actualizado correctamente'];
            },
            'No se pudo cambiar el estado del directorio.'
        );
    }
}
