<?php

namespace App\Http\Controllers\Roles\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Commerce\CommerceProfileRequest;
use App\Services\Roles\Commerce\CommerceProfileService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Autogestión de la ficha propia de un Commerce (comercializadora) —
 * distinto de AdminCommerceController (gestión de terceros, protegido por
 * commerces.*). Aquí el actor SIEMPRE opera sobre su propio registro,
 * resuelto server-side por CommerceProfileService — nunca por un {id} de
 * la URL, así que no requiere una Policy de ownership separada.
 */
final class CommerceProfileController extends Controller
{
    public function __construct(
        private readonly CommerceProfileService $service,
    ) {}

    public function show(): Response
    {
        return Inertia::render('Roles/Commerce/Profile/Index', [
            'title'    => 'Mi Comercio',
            'commerce' => $this->service->showOwn(),
        ]);
    }

    public function update(CommerceProfileRequest $request): JsonResponse
    {
        $this->service->updateOwn($request->validated());

        return response()->json(['message' => 'Datos actualizados correctamente.']);
    }
}
