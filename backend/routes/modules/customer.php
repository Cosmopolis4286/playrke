<?php

use App\Http\Controllers\Roles\CustomerController;

/*
|--------------------------------------------------------------------------
| Rutas de Funcionalidades del Cliente
|--------------------------------------------------------------------------
| Operaciones específicas para usuarios con rol de cliente.
*/

// ====================================
// API CLIENTE (SEAMLESS WALLET)
// ====================================

role_protected_routes(['customer'], 'customer', CustomerController::class, [
    'balance' => ['method' => 'get', 'uri' => 'balance/{userToken}'],
    'user-id' => ['method' => 'get', 'uri' => 'user-id/{userToken}'],
    'increase-balance' => ['method' => 'get', 'uri' => 'increase-balance/{userToken}/{balance?}'],
]);
