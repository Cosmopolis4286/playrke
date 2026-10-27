<?php

use App\Http\Controllers\Marketplace\Admin\EscrowAdminController;
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

Route::middleware(['auth', 'verified', 'multiPermission:inventory.view'])->group(function() {
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/fetch-list', [InventoryController::class, 'fetchList'])->name('inventory.fetch-list');
});

// Consumir un activo durante una partida (cualquier juego) — throttle:20,1
// porque un booster puede usarse varias veces en una sesión de juego, a
// diferencia de listar/comprar que son acciones puntuales.
Route::middleware(['auth', 'verified', 'multiPermission:inventory.view', 'throttle:20,1'])->group(function() {
    Route::post('inventory/consume', [InventoryController::class, 'consume'])->name('inventory.consume');
});

Route::middleware(['auth', 'verified', 'multiPermission:marketplace.listings.view'])->group(function() {
    Route::get('marketplace', [MarketplaceController::class, 'index'])->name('marketplace.index');
    Route::get('marketplace/fetch-list', [MarketplaceController::class, 'fetchList'])->name('marketplace.fetch-list');
});

// Debe registrarse ANTES de marketplace/{listing}: de lo contrario Laravel
// intenta resolver "orders" como {listing} (route-model-binding) y esta ruta
// nunca se alcanza — mismo cuidado que cualquier ruta literal junto a un
// wildcard del mismo prefijo.
Route::middleware(['auth', 'verified', 'multiPermission:marketplace.orders.view'])->group(function() {
    Route::get('marketplace/orders/mine', [MarketplaceController::class, 'myOrders'])->name('marketplace.orders.mine');
});

Route::middleware(['auth', 'verified', 'multiPermission:marketplace.listings.view'])->group(function() {
    Route::get('marketplace/{listing}', [MarketplaceController::class, 'show'])->name('marketplace.show');
});

Route::middleware(['auth', 'verified', 'multiPermission:marketplace.listings.create', 'tos', 'throttle:10,1'])->group(function() {
    Route::post('marketplace/listings', [MarketplaceController::class, 'store'])->name('marketplace.listings.store');
    Route::delete('marketplace/listings/{listing}', [MarketplaceController::class, 'cancel'])->name('marketplace.listings.cancel');
});

// Comprar mueve dinero real del comprador de inmediato (settlement automático,
// ver MarketplaceController::buy()) — throttle:5,1 igual que wallet.deposit/withdraw.
Route::middleware(['auth', 'verified', 'multiPermission:marketplace.orders.create', 'tos', 'throttle:5,1'])->group(function() {
    Route::post('marketplace/listings/{listing}/buy', [MarketplaceController::class, 'buy'])->name('marketplace.listings.buy');
});

// ====================================
// PANEL DE MODERACIÓN (ADMIN/SUPERADMIN)
// Nombre de recurso distinto ("marketplace-escrows", no "marketplace/escrows")
// para no competir con el wildcard marketplace/{listing} de arriba.
// ====================================
Route::middleware(['auth', 'multiPermission:marketplace.manage'])->group(function() {
    Route::get('marketplace-escrows', [EscrowAdminController::class, 'index'])->name('marketplace-escrows.index');
    Route::get('marketplace-escrows/fetch-list', [EscrowAdminController::class, 'fetchList'])->name('marketplace-escrows.fetch-list');
    Route::get('marketplace-escrows/{escrow}', [EscrowAdminController::class, 'show'])->name('marketplace-escrows.show');

    Route::middleware('throttle:30,1')->group(function() {
        Route::post('marketplace-escrows/{escrow}/resolve', [EscrowAdminController::class, 'resolve'])->name('marketplace-escrows.resolve');
    });
});
