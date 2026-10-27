<?php

namespace App\Listeners;

use App\Models\ReferralXpAward;
use App\Models\User;
use App\Services\User\UserLevelService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Otorga el XP de referido al referente SOLO cuando el referido verifica su
 * email — antes se otorgaba de inmediato en RegisteredUserController::store(),
 * sin esperar verificación. Eso permitía "sybil farming": automatizar la
 * creación de muchas cuentas con el propio código de referido (Turnstile +
 * throttle:5,1 por IP son evadibles con proxies rotativos) para acumular XP
 * ilimitado sin que ninguna de las cuentas falsas necesitara ser real.
 *
 * Exigir verificación de email eleva el costo del ataque (requiere bandeja de
 * entrada real por cuenta), y el límite diario por referente (ver
 * MAX_DAILY_REFERRAL_XP) acota el impacto aunque el atacante automatice
 * también la verificación. Cada award queda registrado en
 * referral_xp_awards (incluyendo la IP de registro del referido) para que un
 * admin pueda detectar patrones de abuso (muchas cuentas referidas desde la
 * misma IP) y revertir manualmente — no hay reversión automática, es una
 * decisión de producto: un false-positive automático penalizaría un
 * referente legítimo.
 *
 * Auto-descubierto por Laravel (handle() tipado con el evento, sin registro
 * manual) — mismo mecanismo que App\Listeners\LogFailedQueueJobListener.
 */
class AwardReferralXpOnVerifiedListener
{
    /** Máximo de referidos por los que un mismo referente recibe XP en un día. */
    private const MAX_DAILY_REFERRAL_XP = 10;

    public function handle(Verified $event) : void
    {
        $user = $event->user;

        if (! $user instanceof User || ! $user->referred_by_user_id) {
            return;
        }

        $referrer = User::find($user->referred_by_user_id);
        if (! $referrer || ! $referrer->isActive()) {
            return;
        }

        $todayCount = User::where('referred_by_user_id', $referrer->id)
            ->whereNotNull('email_verified_at')
            ->whereDate('email_verified_at', today())
            ->count();

        if ($todayCount >= self::MAX_DAILY_REFERRAL_XP) {
            Log::info('[AwardReferralXpOnVerifiedListener] Límite diario de XP por referidos alcanzado.', [
                'referrer_id' => (string) $referrer->id,
                'verified_user_id' => (string) $user->id,
            ]);

            return;
        }

        try {
            $levelService = app(UserLevelService::class);
            $xp = $levelService->calculateXpReward('referral_signup');
            $levelService->addXpToUser($referrer, $xp, "Referido verificado: {$user->username}");

            ReferralXpAward::create([
                'referrer_id' => $referrer->id,
                'referred_user_id' => $user->id,
                'xp_amount' => $xp,
                'referred_registration_ip' => $user->registration_ip,
            ]);
        } catch (Throwable $e) {
            Log::warning('[AwardReferralXpOnVerifiedListener] No se pudo otorgar XP de referido: ' . $e->getMessage(), [
                'referrer_id' => (string) $referrer->id,
                'verified_user_id' => (string) $user->id,
            ]);
        }
    }
}
