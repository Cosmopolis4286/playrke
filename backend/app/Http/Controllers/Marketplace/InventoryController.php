<?php

namespace App\Http\Controllers\Marketplace;

use App\Domains\Marketplace\Inventory\Exceptions\AssetNotAvailableException;
use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Domains\Marketplace\Inventory\Services\AssetConsumptionService;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(
        private readonly AssetConsumptionService $consumptionService,
    ) {
    }

    /**
     * GET /inventory
     */
    public function index(): Response
    {
        return Inertia::render('Marketplace/Inventory', [
            'assets'    => $this->serialize($this->ownedAvailableQuery()->get()),
            'currencies' => Currency::where('status', 'ACTIVE')->get(['id', 'iso_code', 'symbol']),
        ]);
    }

    /**
     * GET /inventory/fetch-list
     */
    public function fetchList(): JsonResponse
    {
        return response()->json($this->serialize($this->ownedAvailableQuery()->get()));
    }

    /**
     * POST /inventory/consume
     * body: { asset_definition_code, quantity?, game_group? }
     *
     * Endpoint genérico de consumo — cualquier juego (Bingo, Trivia, Cargas)
     * llama a esta ruta desde su propio frontend cuando el jugador activa un
     * consumible. Este controller no sabe qué juego lo llama; game_group es
     * solo un guard opcional contra consumos cruzados (ver
     * AssetConsumptionService::consumeByCode()).
     */
    public function consume(Request $request): JsonResponse
    {
        $request->validate([
            'asset_definition_code' => ['required', 'string'],
            'quantity'              => ['nullable', 'integer', 'min:1'],
            'game_group'            => ['nullable', 'string'],
        ]);

        try {
            $this->consumptionService->consumeByCode(
                userId: (string) Auth::id(),
                assetDefinitionCode: $request->input('asset_definition_code'),
                quantity: (int) $request->input('quantity', 1),
                expectedGameGroup: $request->input('game_group'),
            );

            return response()->json(['success' => true, 'message' => 'Activo consumido.']);
        } catch (AssetNotAvailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function ownedAvailableQuery()
    {
        return PlayerAsset::ownedBy((string) Auth::id())
            ->available()
            ->with('assetDefinition')
            ->orderByDesc('created_at');
    }

    private function serialize($assets): array
    {
        return $assets->map(fn (PlayerAsset $asset) => [
            'id'          => (string) $asset->id,
            'quantity'    => $asset->quantity,
            'status'      => $asset->status,
            'acquired_via' => $asset->acquired_via,
            'definition'  => [
                'code'                => $asset->assetDefinition->code,
                'name'                => $asset->assetDefinition->name,
                'category'            => $asset->assetDefinition->category,
                'rarity'              => $asset->assetDefinition->rarity,
                'tradable'            => $asset->assetDefinition->tradable,
                'consumable'          => $asset->assetDefinition->consumable,
                'marketplace_enabled' => $asset->assetDefinition->marketplace_enabled,
            ],
            'created_at' => $asset->created_at->toIso8601String(),
        ])->values()->toArray();
    }
}
