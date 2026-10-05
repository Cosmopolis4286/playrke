<?php

namespace App\Http\Controllers\Roles\Customer;

use App\Http\Controllers\Concerns\ResolvesTargetUserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Customer\CustomerProfileRequest;
use App\Services\Roles\Customer\CustomerProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Mi Cliente" — ficha propia del Customer (integración B2B). Mismo patrón que
 * CommerceProfileController: el actor opera sobre su propio registro;
 * admin/superadmin pueden inspeccionar uno ajeno con target_user_id.
 */
final class CustomerProfileController extends Controller
{
    use ResolvesTargetUserId;

    public function __construct(
        private readonly CustomerProfileService $service,
    ) {}

    public function show(Request $request): Response
    {
        $targetUserId = $this->resolveTargetUserId($request);

        return Inertia::render('Roles/Customer/Profile/Index', [
            'title'       => 'Mi Cliente',
            'customer'    => $this->service->showOwn($targetUserId),
            'isAdminView' => $targetUserId !== null,
        ]);
    }

    public function update(CustomerProfileRequest $request): JsonResponse
    {
        $this->service->updateOwn($request->validated(), $this->resolveTargetUserId($request));

        return response()->json(['message' => 'Datos actualizados correctamente.']);
    }
}
