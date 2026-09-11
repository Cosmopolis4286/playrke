<?php

namespace App\Http\Controllers\Content;

use App\DTOs\Content\LandingSectionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\LandingSectionRequest;
use App\Http\Requests\Content\LandingSectionReorderRequest;
use App\Http\Resources\Content\LandingSectionResource;
use App\Models\Content\LandingSection;
use App\Services\Content\LandingSectionCrudService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final class LandingSectionController extends Controller
{
    public function __construct(
        private readonly LandingSectionCrudService $service
    ) {
        $this->authorizeResource(LandingSection::class, 'landingSection');
    }

    public function index(): Response
    {
        return $this->handleServiceInertia(function () {
            return Inertia::render('Content/LandingSections/Index', [
                'sections' => LandingSectionResource::collection($this->service->list()),
            ]);
        });
    }

    public function update(LandingSectionRequest $request, LandingSection $landingSection): JsonResponse
    {
        return $this->handleServiceJson(fn () => [
            'data' => new LandingSectionResource(
                $this->service->updateContent($landingSection, $request->toDTO(LandingSectionDTO::class))
            ),
        ]);
    }

    /**
     * 'toggleStatus' no es una acción resource estándar — authorizeResource()
     * del constructor no la cubre automáticamente.
     */
    public function toggleStatus(LandingSection $landingSection): JsonResponse
    {
        $this->authorize('toggleStatus', $landingSection);

        return $this->handleServiceJson(fn () => [
            'data' => new LandingSectionResource($this->service->toggleStatus($landingSection)),
        ]);
    }

    public function reorder(LandingSectionReorderRequest $request): JsonResponse
    {
        $this->authorize('reorder', LandingSection::class);

        return $this->handleServiceJson(function () use ($request) {
            $this->service->reorder($request->validated('ordered_keys'));

            return ['data' => LandingSectionResource::collection($this->service->list())];
        });
    }
}
