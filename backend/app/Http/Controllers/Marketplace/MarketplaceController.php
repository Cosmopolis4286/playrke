<?php

namespace App\Http\Controllers\Marketplace;

use App\Domains\Marketplace\Escrow\Services\EscrowService;
use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Domains\Marketplace\Models\Listing;
use App\Domains\Marketplace\Models\Order;
use App\Domains\Marketplace\Services\ListingService;
use App\Domains\Marketplace\Services\OrderService;
use App\Exceptions\Domain\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\BuyListingRequest;
use App\Http\Requests\Marketplace\CreateListingRequest;
use App\Models\Currency;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MarketplaceController extends Controller
{
    public function __construct(
        private readonly ListingService $listingService,
        private readonly OrderService $orderService,
        private readonly EscrowService $escrowService,
    ) {
    }

    /**
     * POST /marketplace/listings/{listing}/buy
     *
     * Settlement automático en la misma request (decisión de producto): buy()
     * encadena OrderService::buy() -> Escrow create->fund->lockAsset->release.
     * Cada paso ya es atómico e idempotente por sí mismo (ver
     * MARKETPLACE_ARCHITECTURE_AUDIT.md secciones 17-19) — si la request
     * muere a mitad de la cadena, el Order/Escrow quedan en un estado
     * intermedio consistente y recuperable (no hay un mecanismo de retry
     * automático todavía; ver Fase 8, reconciliación).
     */
    public function buy(BuyListingRequest $request, Listing $listing) : JsonResponse
    {
        try {
            $order = $this->orderService->buy($listing, (string) Auth::id(), (int) $request->input('quantity'));
            $escrow = $this->escrowService->create($order);
            $escrow = $this->escrowService->fund($escrow);
            $escrow = $this->escrowService->lockAsset($escrow);
            $escrow = $this->escrowService->release($escrow);

            return response()->json([
                'success' => true,
                'message' => 'Compra completada.',
                'order_id' => (string) $order->id,
                'escrow_id' => (string) $escrow->id,
                'status' => $escrow->status,
            ]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[MarketplaceController@buy] ' . $e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Error interno al procesar la compra.'], 500);
        }
    }

    /**
     * DELETE /marketplace/listings/{listing}
     */
    public function cancel(Listing $listing) : JsonResponse
    {
        if ((string) $listing->seller_id !== (string) Auth::id()) {
            abort(403, 'No autorizado.');
        }

        try {
            $this->listingService->cancel($listing);

            return response()->json(['success' => true, 'message' => 'Listing cancelado.']);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /marketplace/fetch-list?currency_id=&category=&page=
     */
    public function fetchList(Request $request) : JsonResponse
    {
        $query = $this->activeListingsQuery();

        if ($currencyId = $request->query('currency_id')) {
            $query->where('currency_id', $currencyId);
        }

        if ($category = $request->query('category')) {
            $query->whereHas('playerAsset.assetDefinition', fn ($q) => $q->where('category', $category));
        }

        $perPage = min((int) $request->query('per_page', 20), 100);

        return response()->json($this->serializeListings($query->paginate($perPage)));
    }

    /**
     * GET /marketplace
     */
    public function index() : Response
    {
        return Inertia::render('Marketplace/Index', [
            'listings' => $this->serializeListings($this->activeListingsQuery()->paginate(20)),
            'currencies' => Currency::where('status', 'ACTIVE')->get(['id', 'iso_code', 'symbol']),
        ]);
    }

    /**
     * GET /marketplace/orders/mine
     */
    public function myOrders() : JsonResponse
    {
        $orders = Order::where('buyer_id', (string) Auth::id())
            ->with(['listing.playerAsset.assetDefinition', 'listing.currency'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($orders);
    }

    /**
     * GET /marketplace/{listing}
     */
    public function show(Listing $listing) : JsonResponse
    {
        $listing->load(['seller', 'playerAsset.assetDefinition', 'currency']);

        return response()->json($this->serializeListing($listing));
    }

    /**
     * POST /marketplace/listings
     */
    public function store(CreateListingRequest $request) : JsonResponse
    {
        try {
            $asset = PlayerAsset::findOrFail($request->input('player_asset_id'));

            $listing = $this->listingService->create(
                asset: $asset,
                sellerId: (string) Auth::id(),
                quantity: (int) $request->input('quantity'),
                price: (float) $request->input('price'),
                currencyId: $request->input('currency_id'),
                expiresAt: $request->input('expires_at') ? new DateTime($request->input('expires_at')) : null,
            );

            return response()->json([
                'success' => true,
                'message' => 'Activo publicado en el marketplace.',
                'listing' => $this->serializeListing($listing->load(['playerAsset.assetDefinition', 'currency'])),
            ]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[MarketplaceController@store] ' . $e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Error interno. Intenta de nuevo.'], 500);
        }
    }

    private function activeListingsQuery()
    {
        return Listing::active()
            ->with(['seller', 'playerAsset.assetDefinition', 'currency'])
            ->orderByDesc('created_at');
    }

    private function serializeListing(Listing $listing) : array
    {
        return [
            'id' => (string) $listing->id,
            'seller_id' => (string) $listing->seller_id,
            'seller_name' => trim(($listing->seller->first_name ?? '') . ' ' . ($listing->seller->last_name ?? '')) ?: $listing->seller->username ?? 'Vendedor',
            'quantity' => $listing->quantity,
            'price' => (float) $listing->price,
            'status' => $listing->status,
            'currency' => [
                'id' => (string) $listing->currency->id,
                'symbol' => $listing->currency->symbol,
                'code' => $listing->currency->iso_code,
            ],
            'asset' => [
                'id' => (string) $listing->playerAsset->id,
                'code' => $listing->playerAsset->assetDefinition->code,
                'name' => $listing->playerAsset->assetDefinition->name,
                'category' => $listing->playerAsset->assetDefinition->category,
                'rarity' => $listing->playerAsset->assetDefinition->rarity,
            ],
            'expires_at' => $listing->expires_at?->toIso8601String(),
            'created_at' => $listing->created_at->toIso8601String(),
        ];
    }

    private function serializeListings($paginator)
    {
        $paginator->getCollection()->transform(fn (Listing $listing) => $this->serializeListing($listing));

        return $paginator;
    }
}
