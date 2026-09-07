<?php

use App\Http\Controllers\Directories\Crypto\BinanceController;
use App\Http\Controllers\Marketplace\Crypto\DepositAddressController;
use App\Http\Controllers\MovementTypeController;
use App\Http\Controllers\Wallet\WalletMovementController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletLimitController;
use App\Domains\Wallet\Models\WalletMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Billeteras y Criptomonedas
|--------------------------------------------------------------------------
| Gestión de billeteras digitales, criptomonedas y intercambios.
*/

// ====================================
// BILLETERA DEL JUGADOR
// 'verified': toda la wallet (ver saldo, depositar, retirar) mueve o
// expone dinero real — un email sin verificar no debe tener acceso.
// ====================================
Route::middleware(['auth', 'verified', 'multiPermission:wallet.view,wallet.deposit,wallet.withdraw,wallet.movements'])->group(function () {
    Route::get('wallet',                        [WalletController::class, 'index'])            ->name('wallet.index');

    // throttle:wallet-read (60/min por usuario) — sin esto, un usuario
    // autenticado podía golpear estas 5 lecturas sin límite (scraping/DoS de
    // bajo costo contra la BD); no aplica a wallet.index (render Inertia
    // inicial de página, no un endpoint JSON consultable en loop).
    Route::middleware('throttle:wallet-read')->group(function () {
        Route::get('wallet/movements',              [WalletController::class, 'movements'])        ->name('wallet.movements');
        Route::get('wallet/movements/{id}',         [WalletController::class, 'showMovement'])     ->name('wallet.movements.show');
        Route::get('wallet/directories',            [WalletController::class, 'userDirectories'])  ->name('wallet.directories');
        Route::get('wallet/pending-operation',      [WalletController::class, 'pendingOperation']) ->name('wallet.pending-operation');
        Route::get('wallet/limits',                 [WalletController::class, 'limits'])           ->name('wallet.limits');
    });

    // Fase 5 (esqueleto, ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 12):
    // detrás de config('crypto.deposits_enabled'), false por defecto —
    // TronUsdtProvider es un stub, la dirección devuelta nunca es real.
    Route::get('wallet/deposit-address',        [DepositAddressController::class, 'show'])     ->name('wallet.deposit-address');

    // Operaciones financieras: requieren TOS + throttle antifraude
    // throttle:5,1 → máximo 5 intentos por minuto por usuario
    Route::middleware(['tos', 'throttle:5,1'])->group(function () {
        Route::post('wallet/deposit',  [WalletController::class, 'deposit'])  ->name('wallet.deposit');
        // block-during-impersonation: un admin impersonando a un jugador
        // (ver ImpersonationService) no puede retirar fondos reales de esa
        // cuenta — depositar sí se permite (no mueve dinero fuera del
        // sistema), pero un retiro sí, así que solo esta ruta lo bloquea.
        Route::post('wallet/withdraw', [WalletController::class, 'withdraw']) ->name('wallet.withdraw')->middleware('block-during-impersonation');
    });
});

// ====================================
// COMPROBANTES (autenticado + dueño del movimiento — evita exposición pública
// y fuga entre usuarios: antes solo exigía 'auth', así que cualquier usuario
// logueado que conociera/adivinara el nombre de archivo UUID de OTRO usuario
// podía ver su comprobante de depósito, con datos bancarios y monto).
// ====================================
Route::middleware('auth')->get('wallet/receipt/{filename}', function (string $filename) {
    // Solo letras, números, guiones y extensión — sin path traversal
    if (! preg_match('/^[\w\-]+\.(jpg|jpeg|png|pdf|webp)$/i', $filename)) {
        abort(404);
    }

    $movement = WalletMovement::where('receipt_path', 'wallet/receipts/' . $filename)->first();
    $isOwner  = $movement && (string) $movement->user_id === (string) Auth::id();
    $canManage = Auth::user()?->can('wallet.movements.view') || Auth::user()?->can('wallet.movements.manage');
    abort_unless($isOwner || $canManage, 403);

    $path = storage_path('app/public/wallet/receipts/' . $filename);
    if (! file_exists($path)) {
        abort(404);
    }
    $mime = mime_content_type($path) ?: 'application/octet-stream';
    return response()->file($path, ['Content-Type' => $mime]);
})->name('wallet.receipt');

// ====================================
// GESTIÓN DE MOVIMIENTOS (ADMIN/SUPERADMIN)
// ====================================
Route::middleware(['auth', 'multiPermission:wallet.movements.view,wallet.movements.manage'])->group(function () {
    Route::get('wallet-movements',                          [WalletMovementController::class, 'index'])     ->name('wallet-movements.index');
    Route::get('wallet-movements/fetch',                    [WalletMovementController::class, 'fetchList']) ->name('wallet-movements.fetch');
    Route::get('wallet-movements/{movement}',               [WalletMovementController::class, 'show'])      ->name('wallet-movements.show');

    // throttle:30,1 → protege contra doble-submit accidental (doble click) y
    // limita el impacto de una sesión admin comprometida; el límite es alto
    // porque un admin legítimo puede procesar varias solicitudes por minuto.
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('wallet-movements/{movement}/approve', [WalletMovementController::class, 'approve']) ->name('wallet-movements.approve');
        Route::post('wallet-movements/{movement}/reject',  [WalletMovementController::class, 'reject'])  ->name('wallet-movements.reject');
    });
});

// ====================================
// CATÁLOGO DE TIPOS DE MOVIMIENTO (ADMIN/SUPERADMIN)
// ====================================
// Lectura: basta con movement-types.view (o .manage, quien administra también puede ver).
Route::middleware(['auth', 'multiPermission:movement-types.view,movement-types.manage'])->group(function () {
    Route::get('movement-types',                [MovementTypeController::class, 'index'])        ->name('movement-types.index');
    Route::get('movement-types/fetch-list',      [MovementTypeController::class, 'fetchList'])     ->name('movement-types.fetch-list');
    Route::get('movement-types/{id}',           [MovementTypeController::class, 'show'])          ->name('movement-types.show');
});

// Escritura: requiere movement-types.manage explícito — antes aceptaba
// también .view (solo lectura) porque multiPermission acepta "cualquiera de
// los listados" sin distinguir por verbo HTTP, permitiendo que un rol de solo
// consulta creara/editara/borrara el catálogo que decide qué tipos de
// movimiento suman o restan saldo en toda la wallet.
Route::middleware(['auth', 'multiPermission:movement-types.manage'])->group(function () {
    Route::post('movement-types',               [MovementTypeController::class, 'store'])         ->name('movement-types.store');
    Route::put('movement-types/{id}',           [MovementTypeController::class, 'update'])        ->name('movement-types.update');
    Route::put('movement-types/{id}/delete',    [MovementTypeController::class, 'delete'])        ->name('movement-types.delete');
    Route::put('movement-types/{id}/status',    [MovementTypeController::class, 'toggleStatus'])  ->name('movement-types.status');
});

// ====================================
// LÍMITES DE BILLETERA (ADMIN/SUPERADMIN)
// ====================================
// Lectura: basta con wallet-limits.view (o .manage, quien administra también puede ver).
Route::middleware(['auth', 'multiPermission:wallet-limits.view,wallet-limits.manage'])->group(function () {
    Route::get('wallet-limits',              [WalletLimitController::class, 'index']) ->name('wallet-limits.index');
    Route::get('wallet-limits/{id}',         [WalletLimitController::class, 'show'])  ->name('wallet-limits.show');
});

// Escritura: requiere wallet-limits.manage explícito — mismo motivo que
// movement-types: separar view/manage evita que un rol de solo-lectura pueda
// crear/editar/borrar los topes que determinan cuánto puede mover un jugador.
Route::middleware(['auth', 'multiPermission:wallet-limits.manage'])->group(function () {
    Route::post('wallet-limits',             [WalletLimitController::class, 'store'])        ->name('wallet-limits.store');
    Route::put('wallet-limits/{id}',        [WalletLimitController::class, 'update'])        ->name('wallet-limits.update');
    Route::put('wallet-limits/{id}/delete', [WalletLimitController::class, 'delete'])        ->name('wallet-limits.delete');
    Route::put('wallet-limits/{id}/status', [WalletLimitController::class, 'toggleStatus'])  ->name('wallet-limits.status');
});

// ====================================
// BINANCE (CRIPTOMONEDAS)
// throttle:10,1 — antes sin límite pese a gestionar direcciones destino de
// retiro en cripto; mismo criterio que directory (payments.php).
// ====================================
crud_routes('binance', BinanceController::class, ['auth', 'throttle:10,1'], ['index', 'destroy']);
Route::middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::delete('binance/{id}', [BinanceController::class, 'destroy'])->name('binance.destroy');
    Route::get('binance/{id}/info', [BinanceController::class, 'show'])->name('binance.show');
});
