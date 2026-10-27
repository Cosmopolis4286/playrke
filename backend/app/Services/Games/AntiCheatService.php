<?php

declare(strict_types=1);

namespace App\Services\Games;

use App\Enums\Games\Common\AntiCheatSignalType;
use App\Enums\Games\Common\GameSlug;
use App\Models\AntiCheat\AntiCheatFlag;
use App\Models\AntiCheat\UserSuspension;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fuente de verdad para el registro de señales anti-cheat y la suspensión
 * automática de usuarios reincidentes, compartida entre todos los juegos
 * de la plataforma (Bingo, Trivia, ...).
 *
 * `flag()` NO lanza excepción — persiste la señal y evalúa reincidencia como
 * side-effect. Cada juego decide si, además de registrar, debe bloquear la
 * acción actual (lanzando su propia excepción tras llamar a flag()). Esto
 * evita acoplar "detectar" con "bloquear" dentro del servicio común.
 */
final class AntiCheatService
{
    private int $suspensionThreshold;
    private int $windowMinutes;

    public function __construct()
    {
        $this->suspensionThreshold = (int) config('anticheat.suspension_threshold', 5);
        $this->windowMinutes = (int) config('anticheat.window_minutes', 1440);
    }

    /**
     * Registra una señal sospechosa y evalúa reincidencia. Si la suma de
     * severidad del usuario en la ventana configurada alcanza el umbral,
     * dispara auto-suspensión de forma atómica.
     *
     * @param array<string,mixed> $metadata
     */
    public function flag(
        string $userId,
        GameSlug $game,
        AntiCheatSignalType $signal,
        ?string $gameId = null,
        array $metadata = [],
        ?int $severityOverride = null,
    ) : AntiCheatFlag {
        $severity = $severityOverride ?? $signal->defaultSeverity();

        $flag = DB::transaction(function() use ($userId, $game, $signal, $gameId, $metadata, $severity) {
            $flag = AntiCheatFlag::create([
                'user_id' => $userId,
                'game_slug' => $game->value,
                'game_id' => $gameId,
                'signal_type' => $signal,
                'severity' => $severity,
                'metadata' => $metadata,
            ]);

            $this->evaluateAutoSuspension($userId, $flag);

            return $flag;
        });

        Log::warning('[AntiCheat] Señal registrada', [
            'user_id' => $userId,
            'game' => $game->value,
            'signal_type' => $signal->value,
            'severity' => $severity,
            'flag_id' => $flag->id,
        ]);

        return $flag;
    }

    /**
     * ¿Tiene el usuario una suspensión activa (resolved_at IS NULL)?
     */
    public function hasActiveSuspension(string $userId) : bool
    {
        return UserSuspension::where('user_id', $userId)->whereNull('resolved_at')->exists();
    }

    /**
     * Suma de severidad de flags del usuario en la ventana configurada.
     * Agregado CROSS-JUEGO a propósito: un usuario haciendo trampa en Bingo
     * y Trivia con el mismo patrón de bot debe sumar, no resetear por juego.
     */
    public function recentSeverity(string $userId, ?GameSlug $game = null) : int
    {
        $query = AntiCheatFlag::forUser($userId)->recent($this->windowMinutes);

        if ($game !== null) {
            $query->forGame($game->value);
        }

        return (int) $query->sum('severity');
    }

    /**
     * Idempotente: si ya hay una suspensión activa, no crea una segunda.
     */
    private function evaluateAutoSuspension(string $userId, AntiCheatFlag $triggeringFlag) : void
    {
        if ($this->hasActiveSuspension($userId)) {
            return;
        }

        $total = $this->recentSeverity($userId);

        if ($total < $this->suspensionThreshold) {
            return;
        }

        $triggeringFlag->update(['triggered_suspension' => true]);

        UserSuspension::create([
            'user_id' => $userId,
            'origin' => UserSuspension::ORIGIN_ANTI_CHEAT,
            'reason' => "Umbral de severidad anti-cheat alcanzado ({$total}/{$this->suspensionThreshold}) en {$this->windowMinutes} min.",
            'triggering_flag_id' => $triggeringFlag->id,
            'expires_at' => null,
        ]);

        User::where('id', $userId)->update(['status' => 'SUSPENDED']);

        Log::critical('[AntiCheat] Usuario auto-suspendido', [
            'user_id' => $userId,
            'total_severity' => $total,
            'threshold' => $this->suspensionThreshold,
            'flag_id' => $triggeringFlag->id,
        ]);
    }
}
