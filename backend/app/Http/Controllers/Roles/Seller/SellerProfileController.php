<?php

namespace App\Http\Controllers\Roles\Seller;

use App\Http\Controllers\Concerns\ResolvesTargetUserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Seller\SellerProfileRequest;
use App\Services\Roles\Seller\SellerProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Autogestión de la ficha propia de un Seller (taquilla/vendedor) —
 * distinto de ManagerSellerController (gestión por su Commerce/Subsidiary,
 * protegido por sellers.*). Por defecto el actor opera sobre su propio
 * registro, resuelto server-side por SellerProfileService; admin/superadmin
 * pueden pasar target_user_id para inspeccionar la ficha de un vendedor
 * específico (ver AdminProfileAccessService) — no requiere una Policy de
 * ownership separada porque esa validación ya vive en el servicio.
 */
final class SellerProfileController extends Controller
{
    use ResolvesTargetUserId;

    public function __construct(
        private readonly SellerProfileService $service,
    ) {
    }

    public function show(Request $request) : Response
    {
        $targetUserId = $this->resolveTargetUserId($request);

        return Inertia::render('Roles/Seller/Profile/Index', [
            'title' => 'Mi Perfil de Vendedor',
            'seller' => $this->service->showOwn($targetUserId),
            'isAdminView' => $targetUserId !== null,
        ]);
    }

    public function update(SellerProfileRequest $request) : JsonResponse
    {
        $this->service->updateOwn($request->validated(), $this->resolveTargetUserId($request));

        return response()->json(['message' => 'Datos actualizados correctamente.']);
    }
}
