<?php

use App\Http\Controllers\Roles\Subsidiary\SubsidiaryProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de autogestión de Subsidiary (agencia/sucursal)
|--------------------------------------------------------------------------
| Distinto de admin/subsidiary/* (gestión de terceros, multiPermission
| subsidiaries.*) — aquí un usuario 'subsidiary' opera EXCLUSIVAMENTE sobre
| su propio registro, resuelto server-side por SubsidiaryProfileService,
| nunca por un {id} de la URL.
*/
// subsidiaries.view agregado: permite la vista administrativa
// (target_user_id, ver AdminProfileAccessService) a admin/superadmin — ver
// comentario análogo en routes/modules/seller.php.
Route::middleware(['auth', 'multiPermission:subsidiaries.manage-own,subsidiaries.view'])->group(function () {
    Route::get('subsidiary/profile', [SubsidiaryProfileController::class, 'show'])->name('subsidiary.profile.show');
    Route::put('subsidiary/profile', [SubsidiaryProfileController::class, 'update'])->name('subsidiary.profile.update');
});
