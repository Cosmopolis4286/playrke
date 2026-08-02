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
    Route::get('/data',            [ProfileController::class, 'getProfileData'])   ->name('profile.api.data');
    Route::put('/update',          [ProfileController::class, 'updateProfile'])    ->name('profile.api.update')->middleware('throttle:profile-update');
    Route::patch('/password',      [ProfileController::class, 'changePassword'])   ->name('profile.changePassword')->middleware('throttle:profile-change-password');
    Route::post('/avatar',         [ProfileController::class, 'uploadAvatar'])     ->name('profile.api.upload-avatar')->middleware('throttle:profile-avatar');
    Route::delete('/avatar',       [ProfileController::class, 'deleteAvatar'])     ->name('profile.api.delete-avatar')->middleware('throttle:profile-avatar');
    Route::get('/preferences',     [ProfileController::class, 'getPreferences'])   ->name('profile.api.preferences');
    Route::put('/preferences',     [ProfileController::class, 'updatePreferences'])->name('profile.api.update-preferences');
    Route::get('/stats',           [ProfileController::class, 'getStats'])         ->name('profile.api.stats');

    // ── Sesiones ───────────────────────────────────────────────────────────
    Route::get('/sessions',        [ProfileController::class, 'getSessions'])      ->name('profile.api.sessions');
    Route::delete('/sessions/{id}',[ProfileController::class, 'revokeSession'])    ->name('profile.api.sessions.revoke');
    Route::delete('/sessions',     [ProfileController::class, 'revokeAllSessions'])->name('profile.api.sessions.revoke-all');

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
Route::middleware(['auth', 'web'])->prefix('referrals')->group(function () {
    Route::get('/{referralCode}',   [ReferralController::class, 'dashboard'])->name('referral.dashboard');
    Route::get('/r/{referralCode}', [ReferralController::class, 'link'])     ->name('referral.link');
});

// ── Búsqueda de usuarios (selectores async, ej. anfitrión de sala privada) ──
Route::get('/users/search', [UserSearchController::class, 'search'])
    ->middleware(['auth', 'web'])
    ->name('users.search');
