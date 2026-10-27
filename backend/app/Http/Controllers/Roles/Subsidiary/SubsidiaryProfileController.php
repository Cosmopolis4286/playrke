<?php

namespace App\Http\Controllers\Roles\Subsidiary;

use App\Http\Controllers\Concerns\ResolvesTargetUserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Subsidiary\SubsidiaryProfileRequest;
use App\Services\Roles\Subsidiary\SubsidiaryProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Autogestión de la ficha propia de una Subsidiary (agencia/sucursal) —
 * distinto de AdminSubsidiaryController (gestión de terceros, protegido por
 * subsidiaries.*). Por defecto el actor opera sobre su propio registro,
 * resuelto server-side por SubsidiaryProfileService; admin/superadmin
 * pueden pasar target_user_id para inspeccionar la ficha de una agencia
 * específica (ver AdminProfileAccessService) — no requiere una Policy de
 * ownership separada porque esa validación ya vive en el servicio.
 */
final class SubsidiaryProfileController extends Controller
{
    use ResolvesTargetUserId;

    public function __construct(
        private readonly SubsidiaryProfileService $service,
    ) {
    }

    public function show(Request $request) : Response
    {
        $targetUserId = $this->resolveTargetUserId($request);

        return Inertia::render('Roles/Subsidiary/Profile/Index', [
            'title' => 'Mi Agencia',
            'subsidiary' => $this->service->showOwn($targetUserId),
            'isAdminView' => $targetUserId !== null,
        ]);
    }

    public function update(SubsidiaryProfileRequest $request) : JsonResponse
    {
        $this->service->updateOwn($request->validated(), $this->resolveTargetUserId($request));

        return response()->json(['message' => 'Datos actualizados correctamente.']);
    }
}
