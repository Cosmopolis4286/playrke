<?php

declare(strict_types=1);

namespace App\Services\Games;

use App\Models\DailyMission;
use App\Models\User;
use App\Models\UserDailyMissionProgress;
use App\Services\NotificationService;
use App\Services\User\UserLevelService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Misiones diarias — ver progreso/estrategia-producto-plan.md, Fase 6.
 *
 * No reutiliza Achievement/AchievementService: esos son logros de una sola
 * vez para siempre (whereDoesntHave('users', ...)), incompatibles con algo
 * que se reinicia cada día. Sí reutiliza el mismo punto de enganche
 * (GameCompletionService::reportParticipation(), llamado por Bingo/Trivia
 * al terminar una partida) y el mismo servicio de XP (UserLevelService).
 */
final class DailyMissionService
{
    public function __construct(
        private readonly UserLevelService $userLevelService,
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * Registra el avance del día para todas las misiones activas cuyo
     * requisito coincida con esta partida (games_played siempre;
     * games_won solo si $won). Otorga XP y marca completed_at la primera
     * vez que una misión alcanza su target — llamadas posteriores en el
     * mismo día no vuelven a otorgar XP (progress ya no avanza más allá
     * del target).
     */
    public function recordParticipation(User $user, bool $won): void
    {
        $today = now()->toDateString();

        $requirementTypes = [DailyMission::REQUIREMENT_GAMES_PLAYED];
        if ($won) {
            $requirementTypes[] = DailyMission::REQUIREMENT_GAMES_WON;
        }

        $missions = DailyMission::active()
            ->whereIn('requirement_type', $requirementTypes)
            ->get();

        foreach ($missions as $mission) {
            $this->incrementMission($user, $mission, $today);
        }
    }

    private function incrementMission(User $user, DailyMission $mission, string $today): void
    {
        $justCompleted = DB::transaction(function () use ($user, $mission, $today) {
            $progressRow = UserDailyMissionProgress::where('user_id', $user->id)
                ->where('daily_mission_id', $mission->id)
                ->where('date', $today)
                ->lockForUpdate()
                ->first();

            if (! $progressRow) {
                $progressRow = UserDailyMissionProgress::create([
                    'user_id'          => $user->id,
                    'daily_mission_id' => $mission->id,
                    'date'             => $today,
                    'progress'         => 0,
                ]);
            }

            if ($progressRow->isCompleted()) {
                return false; // ya se completó hoy, no sigue sumando
            }

            $newProgress = $progressRow->progress + 1;
            $justCompleted = $newProgress >= $mission->target;

            $progressRow->update([
                'progress'     => min($newProgress, $mission->target),
                'completed_at' => $justCompleted ? now() : null,
            ]);

            if ($justCompleted && $mission->xp_reward > 0) {
                $this->userLevelService->addXpToUser(
                    $user,
                    $mission->xp_reward,
                    "Misión diaria completada: {$mission->name}",
                );
            }

            return $justCompleted;
        });

        // Notificación FUERA de la transacción (mismo criterio que
        // RegisteredUserController): un fallo notificando no debe revertir
        // el progreso/XP ya guardado.
        if ($justCompleted) {
            try {
                $this->notificationService->createMissionCompletedNotification(
                    (string) $user->id,
                    $mission->code,
                    $mission->name,
                    $mission->xp_reward,
                );
            } catch (\Throwable $e) {
                Log::warning('DailyMissionService: fallo al notificar misión completada.', [
                    'user_id'      => $user->id,
                    'mission_code' => $mission->code,
                    'error'        => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Estado de todas las misiones activas para el usuario, en el día de
     * hoy — incluye las que todavía no tienen fila de progreso (progress=0).
     *
     * @return Collection<int, array{mission: DailyMission, progress: int, target: int, completed: bool}>
     */
    public function todayStatus(User $user): Collection
    {
        $today = now()->toDateString();

        $missions = DailyMission::active()->orderBy('sort_order')->get();

        $progressByMission = UserDailyMissionProgress::where('user_id', $user->id)
            ->where('date', $today)
            ->get()
            ->keyBy('daily_mission_id');

        return $missions->map(function (DailyMission $mission) use ($progressByMission) {
            $row = $progressByMission->get($mission->id);

            return [
                'mission'   => $mission,
                'progress'  => $row->progress ?? 0,
                'target'    => $mission->target,
                'completed' => $row?->isCompleted() ?? false,
            ];
        });
    }
}
