<?php

namespace App\Http\Controllers\Marketplace;

use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
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
