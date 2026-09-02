<?php

namespace App\Http\Controllers\Roles\Seller;

use App\Http\Controllers\Controller;

use App\Models\Roles\Seller;
use App\DTOs\Roles\Seller\Manager\ManagerSellerDTO;
use App\Http\Requests\Roles\Seller\Manager\ManagerSellerRequest;
use App\Services\Roles\Seller\Manager\ManagerSellerService;

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
final class ManagerSellerController extends Controller
{
    /**
     * @param ManagerSellerService $service
     */
    public function __construct(
        protected readonly ManagerSellerService $service
    ) {
        $this->authorizeResource(Seller::class, 'seller');
    }

    /**
     * Vista de vendedores del usuario manager.
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $response = $this->service->context();

                $response['title'] = 'Vendedores';

                return Inertia::render('Roles/Seller/Manager/Index', $response);
            },
            'No se pudo cargar la lista de vendedores.'
        );
    }

    /**
     * Crear un vendedor.
     *
     * @param ManagerSellerRequest $request
     * @return JsonResponse
     */
    public function store(ManagerSellerRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->create(
                    $request->toDTO(ManagerSellerDTO::class)
                ),
            ]
        );
    }

    /**
     * Listado de vendedores filtrados por comercio.
     *
     * @return JsonResponse
     */
    public function fetchList(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->fetchList()]
        );
    }

    /**
     * Opciones de "Manager" (sí mismo + sus subsidiaries) para el select de
     * reasignación en ManagerSellerForm.vue — solo usuarios 'commerce'
     * (viewAny ya cubre commerce/subsidiary/customer/admin, pero un usuario
     * 'subsidiary'/'customer' llamando esto simplemente recibe self=null,
     * subsidiaries=[] al no tener Commerce propio, sin necesidad de una
     * autorización explícita por rol adicional aquí).
     *
     * @return JsonResponse
     */
    public function managerOptions(): JsonResponse
    {
        $this->authorize('viewAny', Seller::class);

        return $this->handleServiceJson(
            fn() => $this->service->managerOptions()
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
     * @param ManagerSellerRequest $request
     * @param Seller $seller
     * @return JsonResponse
     */
    public function update(ManagerSellerRequest $request, Seller $seller): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $seller,
                $request->toDTO(ManagerSellerDTO::class)
            )
        );
    }

    /**
     * Eliminar un vendedor (soft delete).
     *
     * 'delete' no es una acción resource estándar (index/show/store/update/
     * destroy) — authorizeResource() del constructor NO la autoriza
     * automáticamente, a diferencia de show/update/destroy. Requiere
     * $this->authorize() explícito, igual que toggleStatus()/resetPassword().
     *
     * @param Seller $seller
     * @return JsonResponse
     */
    public function delete(Seller $seller): JsonResponse
    {
        $this->authorize('update', $seller);

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
        $this->authorize('toggleStatus', $seller);

        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($seller)
        );
    }

    /**
     * Generar una nueva contraseña para un vendedor.
     *
     * Antes sin ningún $this->authorize() — cualquier commerce/subsidiary/
     * customer autenticado podía resetear la contraseña de un Seller de
     * OTRO comercio/agencia con solo cambiar el {seller} de la URL
     * (account takeover). Ver ManagerSellerPolicy::resetPassword()/ownsSeller().
     *
     * @param Seller $seller
     * @return JsonResponse
     */
    public function resetPassword(Seller $seller): JsonResponse
    {
        $this->authorize('resetPassword', $seller);

        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->resetPassword($seller),
            ]
        );
    }
}
