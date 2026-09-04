<?php

namespace App\Http\Controllers\Roles\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Seller\SellerProfileRequest;
use App\Services\Roles\Seller\SellerProfileService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Autogestión de la ficha propia de un Seller (taquilla/vendedor) —
 * distinto de ManagerSellerController (gestión por su Commerce/Subsidiary,
 * protegido por sellers.*). Aquí el actor SIEMPRE opera sobre su propio
 * registro, resuelto server-side por SellerProfileService — nunca por un
 * {id} de la URL, así que no requiere una Policy de ownership separada.
 */
final class SellerProfileController extends Controller
{
    public function __construct(
        private readonly SellerProfileService $service,
    ) {}

    public function show(): Response
    {
        return Inertia::render('Roles/Seller/Profile/Index', [
            'title'  => 'Mi Perfil de Vendedor',
            'seller' => $this->service->showOwn(),
        ]);
    }

    public function update(SellerProfileRequest $request): JsonResponse
    {
        $this->service->updateOwn($request->validated());

        return response()->json(['message' => 'Datos actualizados correctamente.']);
    }
}
