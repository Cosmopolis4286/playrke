<?php

namespace App\Http\Controllers\Roles\Commerce;

use App\Http\Controllers\Concerns\ResolvesTargetUserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Commerce\CommerceProfileRequest;
use App\Services\Roles\Commerce\CommerceProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Autogestión de la ficha propia de un Commerce (comercializadora) —
 * distinto de AdminCommerceController (gestión de terceros, protegido por
 * commerces.*). Por defecto el actor opera sobre su propio registro,
 * resuelto server-side por CommerceProfileService; admin/superadmin pueden
 * pasar target_user_id para inspeccionar la ficha de un comercio específico
 * (ver AdminProfileAccessService) — no requiere una Policy de ownership
 * separada porque esa validación ya vive en el servicio.
 */
final class CommerceProfileController extends Controller
{
    use ResolvesTargetUserId;

    public function __construct(
        private readonly CommerceProfileService $service,
    ) {
    }

    public function show(Request $request) : Response
    {
        $targetUserId = $this->resolveTargetUserId($request);

        return Inertia::render('Roles/Commerce/Profile/Index', [
            'title' => 'Mi Comercio',
            'commerce' => $this->service->showOwn($targetUserId),
            'isAdminView' => $targetUserId !== null,
        ]);
    }

    public function update(CommerceProfileRequest $request) : JsonResponse
    {
        $this->service->updateOwn($request->validated(), $this->resolveTargetUserId($request));

        return response()->json(['message' => 'Datos actualizados correctamente.']);
    }
}
