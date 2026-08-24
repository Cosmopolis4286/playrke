<?php

namespace App\Services\Roles\Admin\User;

use App\Mail\SpecificPermissionExpiredMail;
use App\Models\Audit;
use App\Models\User;
use App\Models\UserSpecificPermissionGrant;
use App\Services\NotificationService;
use App\Traits\ResolvesSuperadminIds;
use Illuminate\Support\Facades\Mail;

/**
 * Lógica de negocio de expiración de permisos individuales — usada por los
 * comandos programados ExpireSpecificPermissionGrantsCommand (expira +
 * notifica) y NotifyExpiringSpecificPermissionGrantsCommand (aviso previo).
 * Separado en Service (no directo en los Commands) para poder probarse sin
 * pasar por la capa de consola, mismo patrón que CheckExpiredBingoRoomsService.
 */
final class SpecificPermissionExpiryService
{
    use ResolvesSuperadminIds;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Revoca todos los grants activos ya vencidos (expires_at <= now), y
     * notifica (in-app a superadmins + email al afectado) por cada uno.
     *
     * @return int Cantidad de grants expirados en esta corrida.
     */
    public function expireDueGrants(): int
    {
        $expired = UserSpecificPermissionGrant::query()
            ->active()
            ->expired()
            ->with('user')
            ->get();

        foreach ($expired as $grant) {
            $grant->update([
                'revoked_at'     => now(),
                'revoked_reason' => UserSpecificPermissionGrant::REVOKED_REASON_EXPIRED,
            ]);

            $this->logExpiredAudit($grant);
            $this->notifyExpired($grant);
        }

        return $expired->count();
    }

    /**
     * Notifica (solo in-app a superadmins, sin revocar todavía) los grants
     * activos que expiran dentro de $days días y aún no recibieron aviso —
     * marca expiring_notified_at para no repetir el aviso en la próxima
     * corrida del comando.
     *
     * @return int Cantidad de grants avisados en esta corrida.
     */
    public function notifyExpiringSoon(int $days): int
    {
        $expiringSoon = UserSpecificPermissionGrant::query()
            ->active()
            ->expiringSoon($days)
            ->with('user')
            ->get();

        foreach ($expiringSoon as $grant) {
            $this->notifyExpiringSoonOne($grant);
            $grant->update(['expiring_notified_at' => now()]);
        }

        return $expiringSoon->count();
    }

    private function notifyExpired(UserSpecificPermissionGrant $grant): void
    {
        $user = $grant->user;
        if (!$user) {
            return;
        }

        $userName = trim("{$user->first_name} {$user->last_name}") ?: $user->username;

        foreach ($this->superadminIds() as $superadminId) {
            $this->notificationService->createSpecificPermissionExpiryNotification(
                superadminId: $superadminId,
                affectedUserId: (string) $user->id,
                affectedUserName: $userName,
                permissionName: $grant->permission_name,
                alreadyExpired: true,
            );
        }

        if ($user->email && $user->is_email_notifications_enabled) {
            Mail::to($user->email)->queue(new SpecificPermissionExpiredMail($user, $grant));
        }
    }

    private function notifyExpiringSoonOne(UserSpecificPermissionGrant $grant): void
    {
        $user = $grant->user;
        if (!$user) {
            return;
        }

        $userName = trim("{$user->first_name} {$user->last_name}") ?: $user->username;

        foreach ($this->superadminIds() as $superadminId) {
            $this->notificationService->createSpecificPermissionExpiryNotification(
                superadminId: $superadminId,
                affectedUserId: (string) $user->id,
                affectedUserName: $userName,
                permissionName: $grant->permission_name,
                alreadyExpired: false,
                expiresAt: $grant->expires_at?->format('d/m/Y H:i'),
            );
        }
    }

    /**
     * Evento de auditoría manual — el 'updated' automático de
     * UserSpecificPermissionGrant::Auditable ya cubre el diff de columnas,
     * pero un evento con nombre propio ('specific_permission_expired')
     * distingue en el feed de actividad "expiró solo" de "un superadmin lo
     * revocó a mano" (ambos caen en 'updated' si no se registra aparte).
     *
     * actor_type='command': este service corre dentro de un Artisan Command
     * disparado por el scheduler (schedule:run), no dentro de un job de cola
     * — Auditable::resolveActorType() solo resuelve 'job' bajo `queue:work`.
     */
    private function logExpiredAudit(UserSpecificPermissionGrant $grant): void
    {
        Audit::create([
            'user_id'        => null,
            'actor_type'     => 'command',
            'auditable_type' => User::class,
            'auditable_id'   => (string) $grant->user_id,
            'event'          => 'specific_permission_expired',
            'old_values'     => ['permission_name' => $grant->permission_name, 'expires_at' => $grant->expires_at?->toISOString()],
            'new_values'     => ['revoked_reason' => UserSpecificPermissionGrant::REVOKED_REASON_EXPIRED],
        ]);
    }
}
