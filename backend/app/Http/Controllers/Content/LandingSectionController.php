<?php

namespace App\Http\Controllers\Content;

use App\DTOs\Content\LandingSectionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\LandingSectionReorderRequest;
use App\Http\Requests\Content\LandingSectionRequest;
use App\Http\Resources\Content\LandingSectionResource;
use App\Models\Content\LandingSection;
use App\Services\Content\LandingSectionCrudService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final class LandingSectionController extends Controller
{
    public function __construct(
        private readonly LandingSectionCrudService $service,
    ) {
        $this->authorizeResource(LandingSection::class, 'landingSection');
    }

    public function index() : Response
    {
        return $this->handleServiceInertia(function() {
            return Inertia::render('Content/LandingSections/Index', [
                // ->resolve() en vez de pasar la resource collection directa:
                // Inertia espera props ya "planas" (array), pero
                // AnonymousResourceCollection serializa como {"data": [...]}
                // al pasar por json_encode, dejando props.sections como un
                // objeto no iterable en el frontend.
                'sections' => LandingSectionResource::collection($this->service->list())->resolve(),
            ]);
        });
    }

    public function reorder(LandingSectionReorderRequest $request) : JsonResponse
    {
        $this->authorize('reorder', LandingSection::class);

        return $this->handleServiceJson(function() use ($request) {
            $this->service->reorder($request->validated('ordered_keys'));

            // ->resolve(): una ResourceCollection anidada dentro de un array
            // igual hereda el auto-wrapping "data" de Laravel al serializarse,
            // dejando la respuesta final como {data: {data: [...]}}.
            return ['data' => LandingSectionResource::collection($this->service->list())->resolve()];
        });
    }

    /**
     * 'toggleStatus' no es una acción resource estándar — authorizeResource()
     * del constructor no la cubre automáticamente.
     */
    public function toggleStatus(LandingSection $landingSection) : JsonResponse
    {
        $this->authorize('toggleStatus', $landingSection);

        return $this->handleServiceJson(fn () => [
            'data' => new LandingSectionResource($this->service->toggleStatus($landingSection)),
        ]);
    }

    public function update(LandingSectionRequest $request, LandingSection $landingSection) : JsonResponse
    {
        return $this->handleServiceJson(fn () => [
            'data' => new LandingSectionResource(
                $this->service->updateContent($landingSection, $request->toDTO(LandingSectionDTO::class)),
            ),
        ]);
    }
}
