<?php

use App\Http\Controllers\Roles\Treasurer\TreasurerProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de autogestión de Treasurer ("Mi Tesorería")
|--------------------------------------------------------------------------
| Un usuario 'treasurer' opera EXCLUSIVAMENTE sobre su propio registro,
| resuelto server-side por TreasurerProfileService. treasurers.view: vista
| admin (target_user_id, ver AdminProfileAccessService).
*/
Route::middleware(['auth', 'multiPermission:treasurers.manage-own,treasurers.view'])->group(function () {
    Route::get('treasurer/profile', [TreasurerProfileController::class, 'show'])->name('treasurer.profile.show');
    Route::put('treasurer/profile', [TreasurerProfileController::class, 'update'])->name('treasurer.profile.update');
});
