<?php

namespace App\Services\Roles\Admin\User;

use App\Domains\Wallet\Models\WalletMovement;
use App\Domains\Wallet\Services\WalletService;
use App\DTOs\Roles\Admin\User\AdminUserUpdateDTO;
use App\Exceptions\Domain\Roles\Admin\User\AdminUserException;
use App\Models\AntiCheat\AntiCheatFlag;
use App\Models\AntiCheat\UserSuspension;
use App\Models\Audit;
use App\Models\Role;
use App\Models\User;
use App\Services\Games\AntiCheatService;
use App\Services\User\UserLevelService;
use App\Services\User\UserPasswordService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

final class AdminUserService
{
    /** Tamaño de página al paginar el historial completo vía "Cargar más". */
    private const AUDIT_HISTORY_PAGE_SIZE = 15;
    /**
     * Tope superior para un otorgamiento manual de XP en una sola acción —
     * mismo límite que tenía el endpoint self-service ya eliminado
     * (UserLevelController::addXp, ver routes/api.php), preservado aquí
     * como referencia razonable aunque este flujo sí exige rol player,
     * autenticación admin y queda auditado (logAdminAction). Sin tope, un
     * error de tipeo en el campo del formulario podría otorgar un monto
     * absurdamente grande sin ninguna validación.
     */
    private const MAX_XP_GRANT_AMOUNT = 10000;

    /** Tope de eventos mostrados en "Actividad reciente" dentro de la ficha. */
    private const RECENT_AUDITS_LIMIT = 10;

    /**
     * Roles que pueden recibir carga de saldo manual desde el panel admin.
     */
    private const WALLET_LOAD_ALLOWED_ROLES = ['commerce', 'subsidiary', 'seller', 'player'];

    public function __construct(
        private readonly AdminUserContextService $context,
        private readonly UserPasswordService $userPasswordService,
        private readonly WalletService $walletService,
        private readonly UserLevelService $userLevelService,
        private readonly AntiCheatService $antiCheatService,
    ) {
    }

    /**
     * Eliminación lógica (soft delete).
     */
    public function delete(User $user) : void
    {
        $this->guardMutation($user);

        if ($user->status === 'DELETED') {
            throw AdminUserException::alreadyDeleted();
        }

        $user->update(['status' => 'DELETED']);
        $user->delete();
    }

    /**
     * Eliminación lógica (soft delete) de varios usuarios en una sola
     * transacción. Mismo criterio de omisión que toggleStatusBulk().
     *
     * @param array<int, string> $userIds
     *
     * @return array{updated: int, skipped: array<int, array{id: string, name: string, reason: string}>}
     */
    public function deleteBulk(array $userIds) : array
    {
        return DB::transaction(function() use ($userIds) {
            $updated = 0;
            $skipped = [];

            foreach (User::whereIn('id', $userIds)->get() as $user) {
                try {
                    $this->guardMutation($user);

                    if ($user->status === 'DELETED') {
                        throw AdminUserException::alreadyDeleted();
                    }

                    $user->update(['status' => 'DELETED']);
                    $user->delete();
                    $updated++;
                } catch (AdminUserException $e) {
                    $skipped[] = [
                        'id' => (string) $user->id,
                        'name' => trim("{$user->first_name} {$user->last_name}") ?: $user->username,
                        'reason' => trans("errors.{$e->getMessage()}") ?: $e->getMessage(),
                    ];
                }
            }

            return ['updated' => $updated, 'skipped' => $skipped];
        });
    }

    /**
     * Eliminación definitiva (hard delete).
     */
    public function destroy(User $user) : void
    {
        $this->guardMutation($user);

        DB::transaction(function() use ($user) {
            $user->roles()->detach();
            $user->forceDelete();
        });
    }

    /**
     * Cursor sobre TODOS los usuarios que coincidan con los filtros (sin
     * paginar) — mismo patrón que ExternalPlayerAdminService::searchForExport(),
     * evita materializar el catálogo completo en memoria como haría ->get().
     *
     * @return \Illuminate\Support\LazyCollection<int, User>
     */
    public function exportForCsv(array $filters = []) : \Illuminate\Support\LazyCollection
    {
        return $this->context->buildFilteredQuery($filters)->cursor();
    }

    public function fetchList(array $filters = [], int $page = 1) : array
    {
        $data = $this->context->resolveUsers($filters, $page);

        return [
            'rows' => $data['users']['rows'] ?? [],
            'pagination' => $data['pagination'],
        ];
    }

    /**
     * Historial de auditoría paginado de un usuario — usado por el botón
     * "Cargar más" del modal de ficha cuando los RECENT_AUDITS_LIMIT
     * mostrados por defecto no bastan, sin obligar a saltar a /audit.
     */
    public function getUserAuditHistory(User $user, int $page = 1) : array
    {
        $paginator = Audit::ofAuditable(User::class, $user->id)
            ->with('user:id,first_name,last_name,email')
            ->latest('created_at')
            ->paginate(self::AUDIT_HISTORY_PAGE_SIZE, ['*'], 'page', $page);

        return [
            'rows' => $paginator->getCollection()->map(fn (Audit $audit) => $this->mapAuditRow($audit))->values(),
            'has_more' => $paginator->hasMorePages(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    /**
     * Detalle consolidado de un usuario para el modal de ficha (datos básicos,
     * wallet si aplica, nivel/XP si es player, y últimos eventos de
     * auditoría) — antes esta información vivía repartida en modales
     * separados sin un lugar único para verla junta.
     */
    public function getUserDetail(User $user) : array
    {
        $user->load(['country:id,name', 'roles:id,name,prefix']);
        $roleName = strtolower($user->roles->first()?->name ?? '');

        $wallet = $user->hasRole(self::WALLET_LOAD_ALLOWED_ROLES) ? $this->getWalletBalance($user) : null;
        $hasMoreWalletMovements = false;

        if ($wallet !== null) {
            $recentMovements = $this->getRecentWalletMovements($user);
            $wallet['recent_movements'] = $recentMovements;
            $hasMoreWalletMovements = count($recentMovements) >= self::RECENT_AUDITS_LIMIT
                && $user->walletMovements()->count() > self::RECENT_AUDITS_LIMIT;
        }

        $level = $roleName === 'player'
            ? $this->userLevelService->getUserLevelInfo($user)
            : null;

        $gameStats = $roleName === 'player' ? $this->getPlayerGameStats($user) : null;

        // KYC solo tiene sentido de negocio para jugadores (verificación de
        // identidad ligada a operaciones de dinero real) — mismo criterio
        // condicional que wallet/level/game_stats.
        $kyc = $roleName === 'player' ? [
            'level' => $user->kyc_level,
            'status' => $user->kyc_status,
            'verified_at' => $user->kyc_verified_at?->format('d/m/Y H:i:s'),
            'rejection_reason' => $user->kyc_rejection_reason,
        ] : null;

        $security = $roleName === 'player' ? $this->getPlayerSecurityInfo($user) : null;

        $recentAudits = Audit::ofAuditable(User::class, $user->id)
            ->with('user:id,first_name,last_name,email')
            ->latest('created_at')
            ->limit(self::RECENT_AUDITS_LIMIT)
            ->get()
            ->map(fn (Audit $audit) => $this->mapAuditRow($audit));

        $activePermissionsCount = $user->specificPermissionGrants()
            ->active()
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();

        $expiringSoonCount = $user->specificPermissionGrants()
            ->active()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->count();

        return [
            'id' => (string) $user->id,
            'full_name' => trim("{$user->first_name} {$user->last_name}"),
            'username' => $user->username,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'status' => $user->status,
            'country' => $user->country?->name,
            'role_name' => $roleName,
            'role_label' => $user->roles->first()?->name,
            'created_at' => $user->created_at?->format('d/m/Y H:i:s'),
            'last_login_at' => $user->last_login_at?->format('d/m/Y H:i:s'),
            'has_specific_permissions' => $activePermissionsCount > 0,
            'specific_permissions_count' => $activePermissionsCount,
            'specific_permissions_expiring_soon_count' => $expiringSoonCount,
            'wallet' => $wallet,
            'has_more_wallet_movements' => $hasMoreWalletMovements,
            'level' => $level,
            'game_stats' => $gameStats,
            'kyc' => $kyc,
            'security' => $security,
            'recent_audits' => $recentAudits,
            // Igual que achievements_count: si la lista (limit
            // RECENT_AUDITS_LIMIT) no llegó al tope, no puede haber más —
            // evita el count() aparte en el caso común.
            'has_more_audits' => $recentAudits->count() >= self::RECENT_AUDITS_LIMIT
                && Audit::ofAuditable(User::class, $user->id)->count() > self::RECENT_AUDITS_LIMIT,
        ];
    }

    /**
     * Obtener balance de wallet del usuario.
     */
    public function getWalletBalance(User $user) : array
    {
        $wallet = $user->wallets()
            ->with('currency:id,iso_code,symbol,name')
            ->first();

        if (! $wallet) {
            return [
                'balance' => 0,
                'currency' => null,
            ];
        }

        return [
            'balance' => (float) $wallet->balance,
            'currency' => $wallet->currency ? [
                'iso_code' => $wallet->currency->iso_code,
                'symbol' => $wallet->currency->symbol,
                'name' => $wallet->currency->name,
            ] : null,
        ];
    }

    /**
     * Historial de movimientos de billetera paginado — mismo patrón que
     * getUserAuditHistory(), botón "Cargar más" en la sección Billetera de la
     * ficha. Antes solo se veía el balance actual (getWalletBalance()), sin
     * ninguna forma de auditar depósitos/retiros/premios individuales desde
     * el panel admin pese a que WalletMovement ya tiene los índices listos
     * para esto (ver migración add_type_status_index_to_wallet_movements_table).
     */
    public function getWalletMovementHistory(User $user, int $page = 1) : array
    {
        $paginator = $user->walletMovements()
            ->latest('created_at')
            ->paginate(self::AUDIT_HISTORY_PAGE_SIZE, ['*'], 'page', $page);

        return [
            'rows' => $paginator->getCollection()->map(fn (WalletMovement $m) => $this->mapWalletMovementRow($m))->values(),
            'has_more' => $paginator->hasMorePages(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    /**
     * Otorgar XP manualmente al usuario (operación admin).
     */
    public function grantXp(User $user, int $amount, ?string $reason = null) : array
    {
        if (! $user->hasRole('player')) {
            throw AdminUserException::xpGrantRequiresPlayerRole();
        }

        if ($amount <= 0 || $amount > self::MAX_XP_GRANT_AMOUNT) {
            throw AdminUserException::invalidXpAmount();
        }

        $result = $this->userLevelService->addXpToUser(
            $user,
            $amount,
            $reason ?: 'XP otorgado por administrador',
        );

        if (! $result['success']) {
            throw AdminUserException::invalidXpAmount();
        }

        $this->logAdminAction($user, 'xp_granted_by_admin', [
            'amount' => $amount,
            'reason' => $reason ?: 'XP otorgado por administrador',
        ]);

        return $result;
    }

    /**
     * Cargar saldo en wallet del usuario (operación admin).
     */
    public function loadWalletBalance(User $user, float $amount, ?string $reason = null) : string
    {
        if (! $user->hasRole(self::WALLET_LOAD_ALLOWED_ROLES)) {
            throw AdminUserException::walletLoadRequiresEligibleRole();
        }

        if ($user->status !== 'ACTIVE') {
            throw AdminUserException::walletLoadRequiresActiveStatus();
        }

        if ($amount <= 0) {
            throw AdminUserException::invalidWalletAmount();
        }

        $description = $reason ?: 'Carga de saldo por administrador';

        $wallet = $this->walletService->recordAdminDeposit(
            user: $user,
            amount: $amount,
            description: $description,
        );

        $wallet->load('currency');

        $this->logAdminAction($user, 'wallet_loaded_by_admin', [
            'amount' => $amount,
            'currency' => $wallet->currency?->iso_code,
            'reason' => $description,
            'new_balance' => (float) $wallet->balance,
        ]);

        return "Saldo cargado exitosamente: {$wallet->currency->symbol}{$amount}. Nuevo saldo: {$wallet->currency->symbol}{$wallet->balance}";
    }

    /**
     * Resetear contraseña.
     */
    public function resetPassword(User $user) : string
    {
        $this->guardMutation($user);

        if ($user->status !== 'ACTIVE') {
            throw AdminUserException::passwordResetRequiresActiveStatus();
        }

        $password = $this->userPasswordService->resetPassword($user);

        $this->logAdminAction($user, 'password_reset_by_admin', ['reset_by' => 'admin']);

        return $password;
    }

    /**
     * Alternar estado ACTIVE / INACTIVE.
     */
    public function toggleStatus(User $user) : void
    {
        $this->guardMutation($user);

        if ($user->status === 'DELETED') {
            throw AdminUserException::invalidStatusChange();
        }

        $user->update([
            'status' => $user->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE',
        ]);
    }

    /**
     * Alternar estado ACTIVE/INACTIVE de varios usuarios en una sola
     * transacción. A diferencia de toggleStatus(), los usuarios protegidos
     * (cuenta propia, superadmin sin permiso) no abortan el lote completo:
     * se omiten y se reportan en 'skipped' para que el frontend informe con
     * precisión qué se aplicó y qué no.
     *
     * @param array<int, string> $userIds
     *
     * @return array{updated: int, skipped: array<int, array{id: string, name: string, reason: string}>}
     */
    public function toggleStatusBulk(array $userIds) : array
    {
        return DB::transaction(function() use ($userIds) {
            $updated = 0;
            $skipped = [];

            foreach (User::whereIn('id', $userIds)->get() as $user) {
                try {
                    $this->guardMutation($user);

                    if ($user->status === 'DELETED') {
                        throw AdminUserException::invalidStatusChange();
                    }

                    $user->update(['status' => $user->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE']);
                    $updated++;
                } catch (AdminUserException $e) {
                    $skipped[] = [
                        'id' => (string) $user->id,
                        'name' => trim("{$user->first_name} {$user->last_name}") ?: $user->username,
                        'reason' => trans("errors.{$e->getMessage()}") ?: $e->getMessage(),
                    ];
                }
            }

            return ['updated' => $updated, 'skipped' => $skipped];
        });
    }

    /**
     * Actualizar datos básicos (y opcionalmente el rol) de un usuario.
     */
    public function update(User $user, AdminUserUpdateDTO $dto) : void
    {
        $this->guardMutation($user);

        if ($dto->roleId !== null) {
            $this->guardRoleChange($user, $dto->roleId);
        }

        $user->update($dto->toArray());

        if ($dto->roleId !== null) {
            $user->roles()->sync([$dto->roleId]);
        }
    }

    /**
     * Estadísticas de juego del jugador — partidas jugadas/ganadas, ganancias
     * totales y logros desbloqueados. Estos accessors/relaciones ya existían
     * en User (usados por ProfileController para el perfil del propio
     * jugador) pero nunca se habían expuesto a ningún admin: soporte/
     * detección de fraude no tenía forma de ver esta información sin acceso
     * directo a BD.
     */
    private function getPlayerGameStats(User $user) : array
    {
        $achievements = $user->achievements()
            ->wherePivotNotNull('unlocked_at')
            ->orderByDesc('user_achievements.unlocked_at')
            ->limit(10)
            ->get(['achievements.id', 'achievements.name', 'achievements.icon', 'achievements.badge_color'])
            ->map(fn ($achievement) => [
                'id' => $achievement->id,
                'name' => $achievement->name,
                'icon' => $achievement->icon,
                'badge_color' => $achievement->badge_color,
                // El pivot user_achievements es genérico (sin ->using(UserAchievement::class)
                // en User::achievements()), así que unlocked_at llega como string
                // crudo de BD, no como Carbon — \Carbon\Carbon::parse() antes de
                // formatear, igual que se necesitaría en cualquier otro consumidor
                // de este pivot (ver AchievementService.php, que lo deja sin formatear).
                'unlocked_at' => $achievement->pivot->unlocked_at
                    ? \Carbon\Carbon::parse($achievement->pivot->unlocked_at)->format('d/m/Y H:i:s')
                    : null,
            ]);

        // Evita una segunda query idéntica salvo el count(): si la lista (limit
        // 10) no llegó al tope, su tamaño YA es el total real de logros
        // desbloqueados — el count() aparte solo hace falta en el caso menos
        // común (10+ logros) donde el límite pudo haber cortado el total real.
        $achievementsCount = $achievements->count() < 10
            ? $achievements->count()
            : $user->achievements()->wherePivotNotNull('unlocked_at')->count();

        return [
            'games_played' => $user->games_played_count,
            'games_won' => $user->games_won_count,
            'total_winnings' => $user->total_winnings,
            'achievements' => $achievements,
            'achievements_count' => $achievementsCount,
        ];
    }

    /**
     * Estado de seguridad del jugador: suspensión activa (si existe, con
     * quién/por qué) y conteo de señales anti-cheat recientes. AntiCheatService
     * ya detecta y auto-suspende (evaluateAutoSuspension()) y
     * UserSuspension::resolve() ya sabe reactivar — pero hasta ahora ningún
     * admin podía ver por qué un jugador quedó SUSPENDED ni resolverlo desde
     * la UI, solo entrando a la BD.
     */
    private function getPlayerSecurityInfo(User $user) : array
    {
        $activeSuspension = UserSuspension::where('user_id', $user->id)
            ->active()
            ->with('triggeringFlag', 'suspendedBy:id,first_name,last_name')
            ->latest('created_at')
            ->first();

        return [
            'active_suspension' => $activeSuspension ? [
                'id' => (string) $activeSuspension->id,
                'origin' => $activeSuspension->origin,
                'reason' => $activeSuspension->reason,
                'notes' => $activeSuspension->notes,
                'created_at' => $activeSuspension->created_at?->format('d/m/Y H:i:s'),
                'suspended_by' => $activeSuspension->suspendedBy?->full_name,
            ] : null,
            'recent_flags_count' => AntiCheatFlag::forUser((string) $user->id)
                ->recent((int) config('anticheat.window_minutes', 1440))
                ->count(),
        ];
    }

    /** Preview de movimientos recientes embebido en getUserDetail() — ver getWalletMovementHistory() para el paginado completo ("Cargar más"). */
    private function getRecentWalletMovements(User $user) : array
    {
        return $user->walletMovements()
            ->latest('created_at')
            ->limit(self::RECENT_AUDITS_LIMIT)
            ->get()
            ->map(fn (WalletMovement $movement) => $this->mapWalletMovementRow($movement))
            ->values()
            ->all();
    }

    /**
     * Bloquea auto-modificación, mutación de cuentas superadmin por parte de
     * actores que no lo son (mismo criterio que AdministratorService), y
     * mutación de OTRA cuenta admin por parte de un actor admin (solo
     * superadmin puede modificar a otro administrador) — evita que un admin
     * desactive/elimine/resetee la contraseña de un par para quedarse con
     * control exclusivo del panel.
     */
    private function guardMutation(User $user) : void
    {
        if ($user->id === auth()->id()) {
            throw AdminUserException::cannotModifySelf();
        }

        if ($user->hasRole('superadmin') && ! auth()->user()->hasRole('superadmin')) {
            throw AdminUserException::cannotModifySuperadmin();
        }

        if ($user->hasRole('admin') && ! auth()->user()->hasRole('superadmin')) {
            throw AdminUserException::cannotModifyOtherAdmin();
        }
    }

    /**
     * Bloquea asignar el rol superadmin o admin si el actor no es superadmin
     * — un admin no puede promover a nadie (ni a sí mismo, ya bloqueado por
     * guardMutation) a su propio nivel o superior.
     */
    private function guardRoleChange(User $user, string $roleId) : void
    {
        $role = Role::find($roleId);
        $roleName = $role ? strtolower($role->name) : null;

        if ($roleName === 'superadmin' && ! auth()->user()->hasRole('superadmin')) {
            throw AdminUserException::cannotModifySuperadmin();
        }

        if ($roleName === 'admin' && ! auth()->user()->hasRole('superadmin')) {
            throw AdminUserException::cannotAssignAdminRole();
        }
    }

    /**
     * Evento de auditoría manual para acciones admin que no dejan (o no
     * dejan completo) rastro vía el diff automático de Auditable — reset de
     * contraseña no toca ninguna columna, y carga de wallet / otorgo de XP
     * mutan tablas distintas a `users` (wallets/wallet_movements,
     * user_levels), así que sin esto la ficha de usuario no vería nada.
     * Mismo patrón que UserPermissionService::logSyncReason().
     */
    private function logAdminAction(User $user, string $event, array $newValues, array $oldValues = []) : void
    {
        Audit::create([
            'user_id' => Auth::id(),
            'actor_type' => Auth::check() ? 'user' : 'system',
            'auditable_type' => User::class,
            'auditable_id' => (string) $user->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => Request::instance()?->fullUrl(),
            'ip_address' => Request::instance()?->ip(),
            'user_agent' => Request::instance()?->userAgent(),
        ]);
    }

    private function mapAuditRow(Audit $audit) : array
    {
        return [
            'id' => (string) $audit->id,
            'date' => $audit->created_at?->format('d/m/Y H:i:s'),
            'actor' => $audit->user?->full_name ?? 'Sistema',
            'event' => $audit->event,
        ];
    }

    private function mapWalletMovementRow(WalletMovement $movement) : array
    {
        return [
            'id' => (string) $movement->id,
            'date' => $movement->created_at?->format('d/m/Y H:i:s'),
            'type' => $movement->type,
            'status' => $movement->status,
            'amount' => (float) $movement->amount,
            'balance_after' => (float) $movement->balance_after,
            'description' => $movement->description,
        ];
    }
}
