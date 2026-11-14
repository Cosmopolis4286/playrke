<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Index - Punto de entrada limpio y organizado
|--------------------------------------------------------------------------
| Este archivo actúa como índice principal que organiza todas las rutas
| por dominios funcionales. Cada módulo maneja su propia lógica de negocio.
*/

// ====================================
// RUTAS PÚBLICAS (sin autenticación)
// ====================================
require __DIR__ . '/auth.php';                    // Autenticación (Laravel Breeze)
require __DIR__ . '/modules/public.php';          // Landing, información pública
require __DIR__ . '/modules/notifications.php';   // Notificaciones

// ====================================
// RUTAS PROTEGIDAS (requieren login)
// ====================================
require __DIR__ . '/modules/dashboard.php';
require __DIR__ . '/modules/user-profile.php';
require __DIR__ . '/modules/content.php';
require __DIR__ . '/modules/notifications.php';

// ====================================
// RUTAS POR ROLES DE USUARIO
// ====================================
require __DIR__ . '/modules/superadmin.php';
require __DIR__ . '/modules/admin.php';
// require __DIR__ . '/modules/customer.php';
require __DIR__ . '/modules/commerce.php';
require __DIR__ . '/modules/subsidiary.php';
require __DIR__ . '/modules/seller.php';
require __DIR__ . '/modules/customer-profile.php'; // "Mi Cliente" (≠ customer.php, API legada)
require __DIR__ . '/modules/treasurer.php';        // "Mi Tesorería"

// ====================================
// RUTAS DE NEGOCIO ESPECÍFICAS
// ====================================
require __DIR__ . '/modules/external-players.php';
require __DIR__ . '/modules/games.php';
require __DIR__ . '/modules/games/bingo.php';
// module:<key> — apagado desde el panel de módulos, bloquea también las rutas.
Route::middleware('module:games_trivia')->group(__DIR__ . '/modules/games/trivia.php');
require __DIR__ . '/modules/app-parameters.php';
require __DIR__ . '/modules/exchange-rate.php';
require __DIR__ . '/modules/currency.php';
require __DIR__ . '/modules/payments.php';
require __DIR__ . '/modules/wallets.php';
require __DIR__ . '/modules/commissions.php';      // "Mis Comisiones"
Route::middleware('module:marketplace')->group(__DIR__ . '/modules/marketplace.php');
require __DIR__ . '/modules/usdt-payments.php';
require __DIR__ . '/modules/tos.php';
require __DIR__ . '/modules/kyc.php';
// require __DIR__ . '/modules/reports.php';

// ====================================
// UTILIDADES Y SERVICIOS
// ====================================
// require __DIR__ . '/modules/files.php';
require __DIR__ . '/modules/utils.php';

// ====================================
// DESARROLLO Y DEMOS
// ====================================
if (app()->environment(['local', 'staging'])) {
    require __DIR__ . '/modules/dev.php';
}
