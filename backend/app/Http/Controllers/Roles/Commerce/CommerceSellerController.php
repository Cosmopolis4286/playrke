<?php

namespace App\Http\Controllers\Roles\Commerce;

use App\Http\Controllers\Controller;

use App\Models\Roles\Seller;
use App\DTOs\Roles\Commerce\CommerceSellerDTO;
use App\Http\Requests\Roles\Commerce\CommerceSellerRequest;
use App\Services\Roles\Commerce\Seller\CommerceSellerService;

use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración de Vendedores.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class CommerceSellerController extends Controller
{
    /**
     * @param CommerceSellerService $service
     */
    public function __construct(
        protected readonly CommerceSellerService $service
    ) {
        $this->authorizeResource(Seller::class, 'seller');
    }

    /**
     * Vista de vendedores
     *
     * @return Response
     */
    public function sellers(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $response = $this->service->context();

                $response['title'] = 'Vendedores';

                return Inertia::render('Roles/Commerce/Seller/Index', $response);
            },
            'No se pudo cargar la lista de vendedores.'
        );
    }

    /**
     * Crear un vendedor.
     *
     * @param CommerceSellerRequest $request
     * @return JsonResponse
     */
    public function store(CommerceSellerRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->create(
                    $request->toDTO(CommerceSellerDTO::class)
                ),
            ]
        );
    }

    /**
     * Listado de vendedores filtrados por comercio.
     *
     * @return JsonResponse
     */
    public function listFiltered(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->listFiltered()]
        );
    }

    /**
     * Mostrar detalle de un vendedor.
     *
     * @param Seller $seller
     * @return JsonResponse
     */
    public function show(Seller $seller): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->show($seller),
            ]
        );
    }

    /**
     * Actualizar un vendedor.
     *
     * @param CommerceSellerRequest $request
     * @param Seller $seller
     * @return JsonResponse
     */
    public function update(CommerceSellerRequest $request, Seller $seller): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $seller,
                $request->toDTO(CommerceSellerDTO::class)
            )
        );
    }

    /**
     * Eliminar un vendedor (soft delete).
     *
     * @param Seller $seller
     * @return JsonResponse
     */
    public function delete(Seller $seller): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($seller)
        );
    }

    /**
     * Eliminar un vendedor permanentemente (hard delete).
     *
     * @param Seller $seller
     * @return JsonResponse
     */
    public function destroy(Seller $seller): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($seller)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del vendedor.
     *
     * @param Seller $seller
     * @return JsonResponse
     */
    public function toggleStatus(Seller $seller): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($seller)
        );
    }

    /**
     * Generar una nueva contraseña para un vendedor.
     *
     * @param Seller $seller
     * @return JsonResponse
     */
    public function resetPassword(Seller $seller): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->resetPassword($seller),
            ]
        );
    }
}
