<?php

use App\Http\Controllers\ReportsController;

require_once app_path('Helpers/RouteHelper.php');

/*
|--------------------------------------------------------------------------
| Rutas de Reportes y Estadísticas
|--------------------------------------------------------------------------
| Sistema centralizado de reportes para todos los módulos.
*/

// ====================================
// REPORTES GENERALES
// ====================================
route_group('reports', 'reports.', ['auth'], ReportsController::class, [
    'index' => ['method' => 'get', 'uri' => ''],
    'store' => ['method' => 'post', 'uri' => ''],
    'show' => ['method' => 'get', 'uri' => '{id}'],
    'export' => ['method' => 'get', 'uri' => '{id}/export/{format}'],
]);

// ====================================
// REPORTES POR MÓDULOS
// ====================================
// Los reportes específicos de bingo están en games/bingo.php
// Los reportes de pagos están en payments.php