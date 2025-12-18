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
require __DIR__ . '/modules/api-public.php';      // APIs públicas

// ====================================
// RUTAS PROTEGIDAS (requieren login)
// ====================================
require __DIR__ . '/modules/dashboard.php';       // TODO: Crear controllers faltantes
require __DIR__ . '/modules/user-profile.php';    // TODO: Crear ProfileController
require __DIR__ . '/modules/content.php';         // TODO: Crear InformationController

// ====================================
// RUTAS POR ROLES DE USUARIO
// ====================================
require __DIR__ . '/modules/admin.php';           // TODO: Crear AdminController
// require __DIR__ . '/modules/customer.php';        // TODO: Revisar controllers
require __DIR__ . '/modules/commerce.php';        // TODO: Revisar controllers
// require __DIR__ . '/modules/seller.php';          // TODO: Revisar controllers
// require __DIR__ . '/modules/treasurer.php';       // TODO: Revisar controllers
// require __DIR__ . '/modules/other-treasurer.php'; // TODO: Revisar controllers

// ====================================
// RUTAS DE NEGOCIO ESPECÍFICAS
// ====================================
// require __DIR__ . '/modules/games/bingo.php';    // TODO: Revisar controllers
// require __DIR__ . '/modules/payments.php';        // TODO: Revisar controllers
// require __DIR__ . '/modules/wallets.php';         // TODO: Revisar controllers
// require __DIR__ . '/modules/reports.php';         // TODO: Revisar controllers

// ====================================
// UTILIDADES Y SERVICIOS
// ====================================
// require __DIR__ . '/modules/files.php';           // TODO: Revisar controllers
// require __DIR__ . '/modules/utils.php';           // TODO: Revisar controllers

// ====================================
// DESARROLLO Y DEMOS
// ====================================
if (app()->environment(['local', 'staging'])) {
    require __DIR__ . '/modules/dev.php';         // Rutas de desarrollo
}
