<?php

namespace App\Http\Controllers\Roles\Treasurer;

use App\Http\Controllers\Concerns\ResolvesTargetUserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Treasurer\TreasurerProfileRequest;
use App\Services\Roles\Treasurer\TreasurerProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Mi Tesorería" — ficha propia del Treasurer. Mismo patrón que
 * CommerceProfileController: el actor opera sobre su propio registro;
 * admin/superadmin pueden inspeccionar uno ajeno con target_user_id.
 */
final class TreasurerProfileController extends Controller
{
    use ResolvesTargetUserId;

    public function __construct(
        private readonly TreasurerProfileService $service,
    ) {}

    public function show(Request $request): Response
    {
        $targetUserId = $this->resolveTargetUserId($request);

        return Inertia::render('Roles/Treasurer/Profile/Index', [
            'title'       => 'Mi Tesorería',
            'treasurer'   => $this->service->showOwn($targetUserId),
            'isAdminView' => $targetUserId !== null,
        ]);
    }

    public function update(TreasurerProfileRequest $request): JsonResponse
    {
        $this->service->updateOwn($request->validated(), $this->resolveTargetUserId($request));

        return response()->json(['message' => 'Datos actualizados correctamente.']);
    }
}
