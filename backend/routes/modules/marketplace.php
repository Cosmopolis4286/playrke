<?php

use App\Http\Controllers\Marketplace\InventoryController;
use App\Http\Controllers\Marketplace\MarketplaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Marketplace P2P + Inventario
|--------------------------------------------------------------------------
| Ver MARKETPLACE_ARCHITECTURE_AUDIT.md. Publicar/comprar mueve dinero real
| (vía WalletReservationService) — mismo criterio de throttle que wallet.deposit/
| withdraw en modules/wallets.php.
*/

Route::middleware(['auth', 'verified', 'multiPermission:inventory.view'])->group(function () {
    Route::get('inventory',            [InventoryController::class, 'index'])     ->name('inventory.index');
    Route::get('inventory/fetch-list', [InventoryController::class, 'fetchList']) ->name('inventory.fetch-list');
});

Route::middleware(['auth', 'verified', 'multiPermission:marketplace.listings.view'])->group(function () {
    Route::get('marketplace',            [MarketplaceController::class, 'index'])     ->name('marketplace.index');
    Route::get('marketplace/fetch-list', [MarketplaceController::class, 'fetchList']) ->name('marketplace.fetch-list');
});

// Debe registrarse ANTES de marketplace/{listing}: de lo contrario Laravel
// intenta resolver "orders" como {listing} (route-model-binding) y esta ruta
// nunca se alcanza — mismo cuidado que cualquier ruta literal junto a un
// wildcard del mismo prefijo.
Route::middleware(['auth', 'verified', 'multiPermission:marketplace.orders.view'])->group(function () {
    Route::get('marketplace/orders/mine', [MarketplaceController::class, 'myOrders']) ->name('marketplace.orders.mine');
});

Route::middleware(['auth', 'verified', 'multiPermission:marketplace.listings.view'])->group(function () {
    Route::get('marketplace/{listing}', [MarketplaceController::class, 'show']) ->name('marketplace.show');
});

Route::middleware(['auth', 'verified', 'multiPermission:marketplace.listings.create', 'tos', 'throttle:10,1'])->group(function () {
    Route::post('marketplace/listings',              [MarketplaceController::class, 'store'])  ->name('marketplace.listings.store');
    Route::delete('marketplace/listings/{listing}',   [MarketplaceController::class, 'cancel']) ->name('marketplace.listings.cancel');
});

// Comprar mueve dinero real del comprador de inmediato (settlement automático,
// ver MarketplaceController::buy()) — throttle:5,1 igual que wallet.deposit/withdraw.
Route::middleware(['auth', 'verified', 'multiPermission:marketplace.orders.create', 'tos', 'throttle:5,1'])->group(function () {
    Route::post('marketplace/listings/{listing}/buy', [MarketplaceController::class, 'buy']) ->name('marketplace.listings.buy');
});
