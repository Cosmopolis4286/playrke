<?php

namespace App\Services\Auth;

use App\Exceptions\Domain\Auth\ImpersonationException;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Impersonación de cuentas operativas para soporte (SuperAdmin-only, ver
 * role_protected_routes(['role:superadmin'], 'admin/impersonation', ...)).
 *
 * Guarda el admin real en la sesión HTTP (impersonator_id) mientras
 * Auth::login() cambia el usuario autenticado al target — así toda la app
 * (HandleInertiaRequests::share(), policies, etc.) ve al target como
 * usuario activo sin necesitar lógica especial en cada punto, y stop()
 * puede restaurar exactamente al admin original leyendo esa misma sesión.
 *
 * Roles impersonables: ver ALLOWED_TARGET_ROLES. Deliberadamente NO incluye
 * admin/superadmin/customer/treasurer — impersonar una cuenta con permisos
 * administrativos sería escalar privilegios, no dar soporte.
 */
final class ImpersonationService
{
    private const SESSION_KEY = 'impersonator_id';
    private const STARTED_AT_SESSION_KEY = 'impersonation_started_at';

    /**
     * Roles que un superadmin puede impersonar para soporte. 'player' es el
     * caso original; seller/commerce/subsidiary se agregaron porque tienen
     * ficha propia (Mi Perfil, ver AdminProfileAccessService) y casos de
     * soporte reales ("no veo mi comisión", "mi vendedor no puede vender").
     */
    public const ALLOWED_TARGET_ROLES = ['player', 'seller', 'commerce', 'subsidiary'];

    /** Duración máxima de una sesión de soporte antes de restaurarse sola. */
    public const TIMEOUT_MINUTES = 30;

    /**
     * Inicia la impersonación: registra quién impersona a quién (auditoría +
     * log crítico, visible en /audit y /logs) y cambia el usuario autenticado.
     */
    public function start(User $admin, User $target): void
    {
        if ($this->isImpersonating(request())) {
            throw ImpersonationException::alreadyImpersonating();
        }

        if (!$target->hasRole(self::ALLOWED_TARGET_ROLES)) {
            throw ImpersonationException::targetRoleNotAllowed();
        }

        request()->session()->put(self::SESSION_KEY, (string) $admin->id);
        request()->session()->put(self::STARTED_AT_SESSION_KEY, now()->toISOString());

        Auth::login($target);
        request()->session()->regenerate();
        // regenerate() cambia el ID de sesión pero conserva sus datos
        // (incluida la key que acabamos de poner) — necesario igualmente
        // por higiene de sesión al cambiar el usuario autenticado.

        Audit::create([
            'user_id'        => $admin->id,
            'actor_type'     => 'user',
            'auditable_type' => User::class,
            'auditable_id'   => $target->id,
            'event'          => 'impersonation_started',
            'old_values'     => [],
            'new_values'     => ['target_username' => $target->username, 'admin_username' => $admin->username],
            'url'            => request()->fullUrl(),
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);

        Log::critical('[Impersonation] Sesión de soporte iniciada', [
            'admin_id'    => $admin->id,
            'admin_name'  => $admin->username,
            'target_id'   => $target->id,
            'target_name' => $target->username,
        ]);
    }

    /**
     * Termina la impersonación activa y restaura al admin original.
     */
    public function stop(Request $request): User
    {
        $adminId = $request->session()->get(self::SESSION_KEY);

        if (!$adminId) {
            throw ImpersonationException::notImpersonating();
        }

        $impersonatedUser = Auth::user();

        $admin = User::findOrFail($adminId);

        $request->session()->forget(self::SESSION_KEY);
        $request->session()->forget(self::STARTED_AT_SESSION_KEY);
        Auth::login($admin);
        $request->session()->regenerate();

        Audit::create([
            'user_id'        => $admin->id,
            'actor_type'     => 'user',
            'auditable_type' => User::class,
            'auditable_id'   => $impersonatedUser?->id ?? $adminId,
            'event'          => 'impersonation_ended',
            'old_values'     => [],
            'new_values'     => ['target_username' => $impersonatedUser?->username],
            'url'            => $request->fullUrl(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        Log::critical('[Impersonation] Sesión de soporte finalizada', [
            'admin_id'  => $admin->id,
            'target_id' => $impersonatedUser?->id,
        ]);

        return $admin;
    }

    public function isImpersonating(Request $request): bool
    {
        return $request->session()->has(self::SESSION_KEY);
    }

    /**
     * Si la sesión de soporte activa superó TIMEOUT_MINUTES, la termina sola
     * (mismo camino que stop(), auditoría incluida) y devuelve true. Se
     * evalúa en cada request vía EnforceImpersonationTimeout — no hay forma
     * de que un proceso externo (comando scheduled) cierre la sesión HTTP de
     * otro request, así que el chequeo vive del lado del propio request.
     */
    public function checkExpiry(Request $request): bool
    {
        if (!$this->isImpersonating($request)) {
            return false;
        }

        $startedAt = $request->session()->get(self::STARTED_AT_SESSION_KEY);

        if (!$startedAt || now()->diffInMinutes(\Carbon\Carbon::parse($startedAt)) < self::TIMEOUT_MINUTES) {
            return false;
        }

        $this->stop($request);

        return true;
    }

    /**
     * Username del admin real detrás de la sesión impersonada — para el
     * banner en frontend. Null si no hay impersonación activa.
     */
    public function impersonatorName(Request $request): ?string
    {
        $adminId = $request->session()->get(self::SESSION_KEY);

        if (!$adminId) {
            return null;
        }

        return User::find($adminId)?->username;
    }
}
