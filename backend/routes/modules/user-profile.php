<?php

use App\Http\Controllers\Users\{ProfileController, ReferralController, UserSearchController};
use App\Http\Controllers\Api\UserLevelController;
use App\Http\Controllers\Api\AchievementController;
use Illuminate\Support\Facades\Route;

// NOTA: el desbloqueo automático de logros ocurre normalmente desde
// GameCompletionService al terminar una partida (ver progreso/xp-logros-plan.md).
// Esta ruta existe como respaldo/soporte para forzar una re-evaluación manual.

Route::middleware(['auth', 'web'])->prefix('profile')->group(function () {

    // ── Página principal (Inertia) ─────────────────────────────────────────
    Route::get('/',  [ProfileController::class, 'index'])->name('profile.index');

    // ── API JSON ───────────────────────────────────────────────────────────
    // block-during-impersonation (ver ImpersonationService): estas rutas
    // mutan identidad/credenciales/sesiones del jugador — un admin
    // impersonando solo debe poder diagnosticar, no dejar la cuenta en un
    // estado distinto al que tenía o tomar control permanente de ella.
    Route::get('/data',            [ProfileController::class, 'getProfileData'])   ->name('profile.api.data');
    Route::put('/update',          [ProfileController::class, 'updateProfile'])    ->name('profile.api.update')->middleware(['throttle:profile-update', 'block-during-impersonation']);
    Route::patch('/password',      [ProfileController::class, 'changePassword'])   ->name('profile.changePassword')->middleware(['throttle:profile-change-password', 'block-during-impersonation']);
    Route::post('/avatar',         [ProfileController::class, 'uploadAvatar'])     ->name('profile.api.upload-avatar')->middleware(['throttle:profile-avatar', 'block-during-impersonation']);
    Route::delete('/avatar',       [ProfileController::class, 'deleteAvatar'])     ->name('profile.api.delete-avatar')->middleware(['throttle:profile-avatar', 'block-during-impersonation']);
    Route::get('/preferences',     [ProfileController::class, 'getPreferences'])   ->name('profile.api.preferences');
    Route::put('/preferences',     [ProfileController::class, 'updatePreferences'])->name('profile.api.update-preferences');
    Route::get('/stats',           [ProfileController::class, 'getStats'])         ->name('profile.api.stats');

    // ── Sesiones ───────────────────────────────────────────────────────────
    Route::get('/sessions',        [ProfileController::class, 'getSessions'])      ->name('profile.api.sessions');
    Route::delete('/sessions/{id}',[ProfileController::class, 'revokeSession'])    ->name('profile.api.sessions.revoke')->middleware('block-during-impersonation');
    Route::delete('/sessions',     [ProfileController::class, 'revokeAllSessions'])->name('profile.api.sessions.revoke-all')->middleware('block-during-impersonation');

    // ── Niveles ────────────────────────────────────────────────────────────
    Route::get('/level',           [UserLevelController::class, 'getUserLevel'])   ->name('profile.api.level');
    Route::post('/level/add-xp',   [UserLevelController::class, 'addXp'])          ->name('profile.api.level.add-xp')->middleware('throttle:profile-add-xp');

    // ── Logros ─────────────────────────────────────────────────────────────
    Route::get('/achievements',           [AchievementController::class, 'getUserAchievements'])   ->name('profile.api.achievements');
    Route::get('/achievements/unlocked',  [AchievementController::class, 'getUnlockedAchievements'])->name('profile.api.achievements.unlocked');
    Route::post('/achievements/mark-seen',[AchievementController::class, 'markAsSeen'])            ->name('profile.api.achievements.mark-seen');
    Route::post('/achievements/mark-all-seen',[AchievementController::class, 'markAllAsSeen'])     ->name('profile.api.achievements.mark-all-seen');
    Route::get('/achievements/stats',     [AchievementController::class, 'getStats'])              ->name('profile.api.achievements.stats');
    Route::post('/achievements/check',    [AchievementController::class, 'checkAndUnlock'])        ->name('profile.api.achievements.check')->middleware('throttle:profile-achievements-check');
});

// ── Referidos ──────────────────────────────────────────────────────────────
// summary() requiere sesión (datos propios del usuario); link() NO — un
// visitante sin cuenta debe poder abrir el enlace de invitación antes de
// registrarse.
Route::middleware(['auth', 'web'])->get('/profile/referrals', [ReferralController::class, 'summary'])->name('referral.summary');
Route::middleware('web')->get('/referrals/r/{referralCode}', [ReferralController::class, 'link'])->name('referral.link');

// ── Búsqueda de usuarios (selectores async, ej. anfitrión de sala privada) ──
Route::get('/users/search', [UserSearchController::class, 'search'])
    ->middleware(['auth', 'web'])
    ->name('users.search');
