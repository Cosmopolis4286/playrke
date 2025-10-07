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
require __DIR__ . '/modules/dashboard.php';       // Dashboard principal
require __DIR__ . '/modules/user-profile.php';    // Perfil y gestión personal
require __DIR__ . '/modules/content.php';         // Contenido e información

// ====================================
// RUTAS POR ROLES DE USUARIO  
// ====================================
require __DIR__ . '/modules/admin.php';           // Administración del sistema
require __DIR__ . '/modules/customer.php';        // Funciones de clientes
require __DIR__ . '/modules/commerce.php';        // Gestión comercial
require __DIR__ . '/modules/seller.php';          // Vendedores/Agencias
require __DIR__ . '/modules/treasurer.php';
require __DIR__ . '/modules/other-treasurer.php';       // Tesorería y pagos

// ====================================
// RUTAS DE NEGOCIO ESPECÍFICAS
// ====================================
require __DIR__ . '/modules/games/bingo.php';    // Sistema de bingo
require __DIR__ . '/modules/payments.php';        // Métodos de pago
require __DIR__ . '/modules/wallets.php';         // Billeteras y criptos
require __DIR__ . '/modules/reports.php';         // Reportes y estadísticas

// ====================================
// UTILIDADES Y SERVICIOS
// ====================================  
require __DIR__ . '/modules/files.php';           // Manejo de archivos
require __DIR__ . '/modules/utils.php';           // Utilidades varias

// ====================================
// DESARROLLO Y DEMOS
// ====================================
if (app()->environment(['local', 'staging'])) {
    require __DIR__ . '/modules/dev.php';         // Rutas de desarrollo
}