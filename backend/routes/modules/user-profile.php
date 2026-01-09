<?php

use App\Http\Controllers\User\{ProfileController, ReferralController};

/*
|--------------------------------------------------------------------------
| Rutas de Perfil de Usuario
|--------------------------------------------------------------------------
| Gestión del perfil personal, configuraciones de cuenta y referencias.
*/

// ====================================
// PERFIL PERSONAL DEL USUARIO
// ====================================
route_group('profile', 'profile.', ['auth'], ProfileController::class, [
    'index' => ['method' => 'get', 'uri' => ''],
    'update' => ['method' => 'put', 'uri' => '{id}'],
    'changePassword' => ['method' => 'patch', 'uri' => '{id}/password'],
]);

// ====================================
// SISTEMA DE REFERIDOS
// ====================================
route_group('referrals', 'referral.', ['auth'], ReferralController::class, [
    'dashboard' => ['method' => 'get', 'uri' => '{referralCode}'],
    'link' => ['method' => 'get', 'uri' => 'r/{referralCode}'],
]);
