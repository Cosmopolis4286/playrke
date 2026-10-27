<?php

use App\Http\Controllers\Roles\Customer\CustomerProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de autogestión de Customer ("Mi Cliente")
|--------------------------------------------------------------------------
| Módulo propio, separado de routes/modules/customer.php (API seamless-wallet
| legada, deshabilitada en web.php). Aquí un usuario 'customer' opera
| EXCLUSIVAMENTE sobre su propio registro, resuelto server-side por
| CustomerProfileService. customers.view: vista admin (target_user_id).
*/
Route::middleware(['auth', 'multiPermission:customers.manage-own,customers.view'])->group(function() {
    Route::get('customer/profile', [CustomerProfileController::class, 'show'])->name('customer.profile.show');
    Route::put('customer/profile', [CustomerProfileController::class, 'update'])->name('customer.profile.update');
});
