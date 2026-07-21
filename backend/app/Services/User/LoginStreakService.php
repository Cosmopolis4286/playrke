<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Registra cada login exitoso y mantiene la racha de días consecutivos.
 *
 * Único punto de entrada esperado: AuthenticatedSessionController::store(), el
 * único flujo de login activo de la app (Fortify no procesa logins reales aquí,
 * ver progreso/xp-logros-plan.md Fase 5). Sigue el mismo patrón de
 * GameCompletionService: método público único, escritura en transacción,
 * evaluación de logros con fallo contenido.
 *
 * Reglas (server corre en UTC, ver config/app.php):
 *  - Primer login o último login antes de ayer → racha = 1.
 *  - Último login fue ayer (UTC) → racha += 1.
 *  - Último login fue hoy (UTC) → no se toca la racha (ya contabilizada este día).
 */
final class LoginStreakService
{
    public function __construct(
        private readonly AchievementService $achievementService,
    ) {}

    public function registerLogin(User $user, ?string $ip = null): void
    {
        DB::transaction(function () use ($user, $ip) {
            $previousLoginAt = $user->last_login_at;
            $now = Carbon::now();

            if ($previousLoginAt === null || ! $previousLoginAt->isToday()) {
                $newStreak = $previousLoginAt !== null && $previousLoginAt->isYesterday()
                    ? $user->login_streak_days + 1
                    : 1;

                $user->login_streak_days = $newStreak;
                $user->best_login_streak_days = max($user->best_login_streak_days, $newStreak);
            }

            $user->last_login_at = $now;
            $user->last_login_ip = $ip;
            $user->save();
        });

        try {
            $this->achievementService->checkAndUnlockAchievements($user->fresh(), [
                'login_streak' => $user->login_streak_days,
            ]);
        } catch (\Throwable $e) {
            // El login y la racha ya quedaron guardados; un fallo evaluando
            // logros no debe impedir que el usuario entre a la plataforma.
            Log::warning('LoginStreakService: fallo al evaluar logros de racha.', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
