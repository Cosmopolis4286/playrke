<?php

use App\Http\Controllers\Users\{ProfileController, ReferralController};
use App\Http\Controllers\Api\UserLevelController;
use App\Http\Controllers\Api\AchievementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Perfil de Usuario
|--------------------------------------------------------------------------
| Gestión del perfil personal, configuraciones de cuenta y referencias.
*/

// ====================================
// PERFIL PERSONAL DEL USUARIO
// ====================================
Route::middleware(['auth', 'web'])->prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/{id}', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/password', [ProfileController::class, 'changePassword'])->name('profile.changePassword');
    
    // Rutas de API para el perfil (usadas por frontend)
    Route::get('/user', [ProfileController::class, 'getProfileData'])->name('profile.api.user');
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.api.update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('profile.api.update-password');
    Route::post('/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.api.upload-avatar');
    Route::delete('/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.api.delete-avatar');
    Route::get('/preferences', [ProfileController::class, 'getPreferences'])->name('profile.api.preferences');
    Route::put('/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.api.update-preferences');
    Route::get('/stats', [ProfileController::class, 'getStats'])->name('profile.api.stats');
    
    // Rutas de Sistema de Niveles
    Route::get('/level', [UserLevelController::class, 'getUserLevel'])->name('profile.api.level');
    Route::post('/level/add-xp', [UserLevelController::class, 'addXp'])->name('profile.api.level.add-xp');
    Route::post('/level/remove-xp', [UserLevelController::class, 'removeXp'])->name('profile.api.level.remove-xp');
    Route::post('/level/reset', [UserLevelController::class, 'resetLevel'])->name('profile.api.level.reset');
    
    // Rutas de Sistema de Logros
    Route::get('/achievements', [AchievementController::class, 'getUserAchievements'])->name('profile.api.achievements');
    Route::get('/achievements/all', [AchievementController::class, 'index'])->name('profile.api.achievements.all');
    Route::get('/achievements/unlocked', [AchievementController::class, 'getUnlockedAchievements'])->name('profile.api.achievements.unlocked');
    Route::get('/achievements/unseen', [AchievementController::class, 'getUnseenAchievements'])->name('profile.api.achievements.unseen');
    Route::post('/achievements/mark-seen', [AchievementController::class, 'markAsSeen'])->name('profile.api.achievements.mark-seen');
    Route::post('/achievements/mark-all-seen', [AchievementController::class, 'markAllAsSeen'])->name('profile.api.achievements.mark-all-seen');
    Route::get('/achievements/stats', [AchievementController::class, 'getStats'])->name('profile.api.achievements.stats');
    Route::post('/achievements/check', [AchievementController::class, 'checkAndUnlock'])->name('profile.api.achievements.check');
    Route::get('/achievements/category/{category}', [AchievementController::class, 'getByCategory'])->name('profile.api.achievements.category');
});

// ====================================
// SISTEMA DE REFERIDOS
// ====================================
Route::middleware(['auth', 'web'])->prefix('referrals')->group(function () {
    Route::get('/{referralCode}', [ReferralController::class, 'dashboard'])->name('referral.dashboard');
    Route::get('/r/{referralCode}', [ReferralController::class, 'link'])->name('referral.link');
});
