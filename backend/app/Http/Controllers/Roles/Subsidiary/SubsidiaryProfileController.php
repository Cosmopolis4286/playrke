<?php

namespace App\Http\Controllers\Roles\Subsidiary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Subsidiary\SubsidiaryProfileRequest;
use App\Services\Roles\Subsidiary\SubsidiaryProfileService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Autogestión de la ficha propia de una Subsidiary (agencia/sucursal) —
 * distinto de AdminSubsidiaryController (gestión de terceros, protegido por
 * subsidiaries.*). Aquí el actor SIEMPRE opera sobre su propio registro,
 * resuelto server-side por SubsidiaryProfileService — nunca por un {id} de
 * la URL, así que no requiere una Policy de ownership separada.
 */
final class SubsidiaryProfileController extends Controller
{
    public function __construct(
        private readonly SubsidiaryProfileService $service,
    ) {}

    public function show(): Response
    {
        return Inertia::render('Roles/Subsidiary/Profile/Index', [
            'title'      => 'Mi Agencia',
            'subsidiary' => $this->service->showOwn(),
        ]);
    }

    public function update(SubsidiaryProfileRequest $request): JsonResponse
    {
        $this->service->updateOwn($request->validated());

        return response()->json(['message' => 'Datos actualizados correctamente.']);
    }
}
