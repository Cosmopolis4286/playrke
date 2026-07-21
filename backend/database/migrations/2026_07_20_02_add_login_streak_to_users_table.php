<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el conteo de racha de login consecutiva a `users`.
 *
 * No existía ninguna lógica de racha (ver AchievementService::shouldUnlockAchievement,
 * caso 'login_streak', que siempre retornaba false) — los logros streak_7/streak_30
 * del catálogo eran indesbloqueables. `login_streak_days` es la racha actual
 * (se resetea a 1 si se salta un día); `best_login_streak_days` es el máximo
 * histórico alcanzado, necesario porque ProfileController::buildStats() expone
 * tanto "racha actual" como "mejor racha" al frontend (GameStatsTab.vue).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('login_streak_days')->default(0)->after('last_login_ip');
            $table->unsignedInteger('best_login_streak_days')->default(0)->after('login_streak_days');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login_streak_days', 'best_login_streak_days']);
        });
    }
};
