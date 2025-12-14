<?php

use App\Http\Controllers\User\Roles\{AdminController, CustomerController};
use App\Http\Controllers\User\Roles\Treasurer\{TreasurerController, OtherTreasurersController};
use Illuminate\Support\Facades\Route;

require_once app_path('Helpers/RouteHelper.php');

/*
|--------------------------------------------------------------------------
| Rutas de Administración del Sistema
|--------------------------------------------------------------------------
| Todas las funciones administrativas: gestión de usuarios, comercios,
| tesoreros, y configuraciones del sistema.
*/

// ====================================
// PANEL ADMINISTRATIVO PRINCIPAL
// ====================================
role_protected_routes(['role:admin'], 'admin', AdminController::class, [
    'dashboard' => ['method' => 'get', 'uri' => ''],
    'customers' => ['method' => 'get', 'uri' => 'customers'],
    'commerces' => ['method' => 'get', 'uri' => 'commerces'],
    'sellers' => ['method' => 'get', 'uri' => 'sellers'],
    'treasurers' => ['method' => 'get', 'uri' => 'treasurers'],
    'other-treasurers' => ['method' => 'get', 'uri' => 'other-treasurers'],
    'subsidiaries' => ['method' => 'get', 'uri' => 'subsidiaries'],
    'users' => ['method' => 'get', 'uri' => 'users'],
    'getUserInfo' => ['method' => 'get', 'uri' => 'get-user-info/{userRole}/{exclude?}'],
]);

// ====================================
// GESTIÓN DE CLIENTES (CRUD COMPLETO)
// ====================================
role_protected_routes(['admin'], 'customer', CustomerController::class, [
    'create' => ['method' => 'post', 'uri' => ''],
    'show' => ['method' => 'get', 'uri' => '{id}'],
    'update' => ['method' => 'put', 'uri' => '{id}'],
    'destroy' => ['method' => 'delete', 'uri' => '{id}'],
    'changePassword' => ['method' => 'put', 'uri' => '{id}/password'],
    'changeStatus' => ['method' => 'put', 'uri' => '{id}/status'],
]);

// ====================================
// GESTIÓN DE TESOREROS (CRUD COMPLETO)
// ====================================
role_protected_routes(['admin'], 'treasurer', TreasurerController::class, [
    'create' => ['method' => 'post', 'uri' => ''],
    'show' => ['method' => 'get', 'uri' => '{id}'],
    'update' => ['method' => 'put', 'uri' => '{id}'],
    'destroy' => ['method' => 'delete', 'uri' => '{id}'],
    'changePassword' => ['method' => 'put', 'uri' => '{id}/password'],
    'changeStatus' => ['method' => 'put', 'uri' => '{id}/status'],
]);

// Ruta especial de tesorero sin middleware admin
Route::middleware('auth')
    ->get('treasurer/payment-details/{treasurer}/{currency}', [TreasurerController::class, 'getPaymentTreasurerDetails'])
    ->name('treasurer.payment-details');

// ====================================
// GESTIÓN DE OTROS TESOREROS
// ====================================
role_protected_routes(['admin'], 'other-treasurer', OtherTreasurersController::class, [
    'show' => ['method' => 'get', 'uri' => '{id}'],
    'update' => ['method' => 'put', 'uri' => '{id}'],
    'destroy' => ['method' => 'delete', 'uri' => '{id}'],
    'changeStatus' => ['method' => 'put', 'uri' => '{id}/status'],
]);
