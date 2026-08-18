<?php

namespace App\Services\Roles\Admin\User;

use App\DTOs\Roles\Admin\User\AdminUserUpdateDTO;
use App\Exceptions\Domain\Roles\Admin\User\AdminUserException;
use App\Models\Audit;
use App\Models\Role;
use App\Models\User;
use App\Services\User\UserLevelService;
use App\Services\User\UserPasswordService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

final class AdminUserService
{
    public function __construct(
        private readonly AdminUserContextService $context,
        private readonly UserPasswordService     $userPasswordService,
        private readonly WalletService           $walletService,
        private readonly UserLevelService        $userLevelService,
    ) {}

    public function fetchList(array $filters = [], int $page = 1): array
    {
        $data = $this->context->resolveUsers($filters, $page);

        return [
            'rows'       => $data['users']['rows'] ?? [],
            'pagination' => $data['pagination'],
        ];
    }

    /**
     * Cursor sobre TODOS los usuarios que coincidan con los filtros (sin
     * paginar) — mismo patrón que ExternalPlayerAdminService::searchForExport(),
     * evita materializar el catálogo completo en memoria como haría ->get().
     *
     * @return \Illuminate\Support\LazyCollection<int, User>
     */
    public function exportForCsv(array $filters = []): \Illuminate\Support\LazyCollection
    {
        return $this->context->buildFilteredQuery($filters)->cursor();
    }

    /**
     * Actualizar datos básicos (y opcionalmente el rol) de un usuario.
     */
    public function update(User $user, AdminUserUpdateDTO $dto): void
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
     * Alternar estado ACTIVE / INACTIVE.
     */
    public function toggleStatus(User $user): void
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
     * Eliminación lógica (soft delete).
     */
    public function delete(User $user): void
    {
        $this->guardMutation($user);

        if ($user->status === 'DELETED') {
            throw AdminUserException::alreadyDeleted();
        }

        $user->update(['status' => 'DELETED']);
        $user->delete();
    }

    /**
     * Eliminación definitiva (hard delete).
     */
    public function destroy(User $user): void
    {
        $this->guardMutation($user);

        DB::transaction(function () use ($user) {
            $user->roles()->detach();
            $user->forceDelete();
        });
    }

    /**
     * Resetear contraseña.
     */
    public function resetPassword(User $user): string
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
     * Bloquea auto-modificación, mutación de cuentas superadmin por parte de
     * actores que no lo son (mismo criterio que AdministratorService), y
     * mutación de OTRA cuenta admin por parte de un actor admin (solo
     * superadmin puede modificar a otro administrador) — evita que un admin
     * desactive/elimine/resetee la contraseña de un par para quedarse con
     * control exclusivo del panel.
     */
    private function guardMutation(User $user): void
    {
        if ($user->id === auth()->id()) {
            throw AdminUserException::cannotModifySelf();
        }

        if ($user->hasRole('superadmin') && !auth()->user()->hasRole('superadmin')) {
            throw AdminUserException::cannotModifySuperadmin();
        }

        if ($user->hasRole('admin') && !auth()->user()->hasRole('superadmin')) {
            throw AdminUserException::cannotModifyOtherAdmin();
        }
    }

    /**
     * Alternar estado ACTIVE/INACTIVE de varios usuarios en una sola
     * transacción. A diferencia de toggleStatus(), los usuarios protegidos
     * (cuenta propia, superadmin sin permiso) no abortan el lote completo:
     * se omiten y se reportan en 'skipped' para que el frontend informe con
     * precisión qué se aplicó y qué no.
     *
     * @param array<int, string> $userIds
     * @return array{updated: int, skipped: array<int, array{id: string, name: string, reason: string}>}
     */
    public function toggleStatusBulk(array $userIds): array
    {
        return DB::transaction(function () use ($userIds) {
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
     * Eliminación lógica (soft delete) de varios usuarios en una sola
     * transacción. Mismo criterio de omisión que toggleStatusBulk().
     *
     * @param array<int, string> $userIds
     * @return array{updated: int, skipped: array<int, array{id: string, name: string, reason: string}>}
     */
    public function deleteBulk(array $userIds): array
    {
        return DB::transaction(function () use ($userIds) {
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
     * Bloquea asignar el rol superadmin o admin si el actor no es superadmin
     * — un admin no puede promover a nadie (ni a sí mismo, ya bloqueado por
     * guardMutation) a su propio nivel o superior.
     */
    private function guardRoleChange(User $user, string $roleId): void
    {
        $role = Role::find($roleId);
        $roleName = $role ? strtolower($role->name) : null;

        if ($roleName === 'superadmin' && !auth()->user()->hasRole('superadmin')) {
            throw AdminUserException::cannotModifySuperadmin();
        }

        if ($roleName === 'admin' && !auth()->user()->hasRole('superadmin')) {
            throw AdminUserException::cannotAssignAdminRole();
        }
    }

    /**
     * Roles que pueden recibir carga de saldo manual desde el panel admin.
     */
    private const WALLET_LOAD_ALLOWED_ROLES = ['commerce', 'seller', 'player'];

    /** Tope de eventos mostrados en "Actividad reciente" dentro de la ficha. */
    private const RECENT_AUDITS_LIMIT = 10;

    /** Tamaño de página al paginar el historial completo vía "Cargar más". */
    private const AUDIT_HISTORY_PAGE_SIZE = 15;

    /**
     * Cargar saldo en wallet del usuario (operación admin).
     */
    public function loadWalletBalance(User $user, float $amount, ?string $reason = null): string
    {
        if (!$user->hasRole(self::WALLET_LOAD_ALLOWED_ROLES)) {
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
            user:        $user,
            amount:      $amount,
            description: $description,
        );

        $wallet->load('currency');

        $this->logAdminAction($user, 'wallet_loaded_by_admin', [
            'amount'      => $amount,
            'currency'    => $wallet->currency?->iso_code,
            'reason'      => $description,
            'new_balance' => (float) $wallet->balance,
        ]);

        return "Saldo cargado exitosamente: {$wallet->currency->symbol}{$amount}. Nuevo saldo: {$wallet->currency->symbol}{$wallet->balance}";
    }

    /**
     * Otorgar XP manualmente al usuario (operación admin).
     */
    public function grantXp(User $user, int $amount, ?string $reason = null): array
    {
        if (!$user->hasRole('player')) {
            throw AdminUserException::xpGrantRequiresPlayerRole();
        }

        if ($amount <= 0) {
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
     * Evento de auditoría manual para acciones admin que no dejan (o no
     * dejan completo) rastro vía el diff automático de Auditable — reset de
     * contraseña no toca ninguna columna, y carga de wallet / otorgo de XP
     * mutan tablas distintas a `users` (wallets/wallet_movements,
     * user_levels), así que sin esto la ficha de usuario no vería nada.
     * Mismo patrón que UserPermissionService::logSyncReason().
     */
    private function logAdminAction(User $user, string $event, array $newValues, array $oldValues = []): void
    {
        Audit::create([
            'user_id'        => Auth::id(),
            'actor_type'     => Auth::check() ? 'user' : 'system',
            'auditable_type' => User::class,
            'auditable_id'   => (string) $user->getKey(),
            'event'          => $event,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'url'            => Request::instance()?->fullUrl(),
            'ip_address'     => Request::instance()?->ip(),
            'user_agent'     => Request::instance()?->userAgent(),
        ]);
    }

    /**
     * Obtener balance de wallet del usuario.
     */
    public function getWalletBalance(User $user): array
    {
        $wallet = $user->wallets()
            ->with('currency:id,iso_code,symbol,name')
            ->first();
        
        if (!$wallet) {
            return [
                'balance' => 0,
                'currency' => null
            ];
        }

        return [
            'balance' => (float) $wallet->balance,
            'currency' => $wallet->currency ? [
                'iso_code' => $wallet->currency->iso_code,
                'symbol' => $wallet->currency->symbol,
                'name' => $wallet->currency->name,
            ] : null
        ];
    }

    /**
     * Detalle consolidado de un usuario para el modal de ficha (datos básicos,
     * wallet si aplica, nivel/XP si es player, y últimos eventos de
     * auditoría) — antes esta información vivía repartida en modales
     * separados sin un lugar único para verla junta.
     */
    public function getUserDetail(User $user): array
    {
        $user->load(['country:id,name', 'roles:id,name,prefix']);
        $roleName = strtolower($user->roles->first()?->name ?? '');

        $wallet = $user->hasRole(self::WALLET_LOAD_ALLOWED_ROLES) ? $this->getWalletBalance($user) : null;

        $level = $roleName === 'player'
            ? $this->userLevelService->getUserLevelInfo($user)
            : null;

        $gameStats = $roleName === 'player' ? $this->getPlayerGameStats($user) : null;

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
            'id'                          => (string) $user->id,
            'full_name'                   => trim("{$user->first_name} {$user->last_name}"),
            'username'                    => $user->username,
            'email'                       => $user->email,
            'phone_number'                => $user->phone_number,
            'status'                      => $user->status,
            'country'                     => $user->country?->name,
            'role_name'                   => $roleName,
            'role_label'                  => $user->roles->first()?->name,
            'created_at'                  => $user->created_at?->format('d/m/Y H:i:s'),
            'last_login_at'               => $user->last_login_at?->format('d/m/Y H:i:s'),
            'has_specific_permissions'    => $activePermissionsCount > 0,
            'specific_permissions_count'  => $activePermissionsCount,
            'specific_permissions_expiring_soon_count' => $expiringSoonCount,
            'wallet'                      => $wallet,
            'level'                       => $level,
            'game_stats'                  => $gameStats,
            'recent_audits'               => $recentAudits,
            'has_more_audits'             => Audit::ofAuditable(User::class, $user->id)->count() > self::RECENT_AUDITS_LIMIT,
        ];
    }

    /**
     * Historial de auditoría paginado de un usuario — usado por el botón
     * "Cargar más" del modal de ficha cuando los RECENT_AUDITS_LIMIT
     * mostrados por defecto no bastan, sin obligar a saltar a /audit.
     */
    public function getUserAuditHistory(User $user, int $page = 1): array
    {
        $paginator = Audit::ofAuditable(User::class, $user->id)
            ->with('user:id,first_name,last_name,email')
            ->latest('created_at')
            ->paginate(self::AUDIT_HISTORY_PAGE_SIZE, ['*'], 'page', $page);

        return [
            'rows'        => $paginator->getCollection()->map(fn (Audit $audit) => $this->mapAuditRow($audit))->values(),
            'has_more'    => $paginator->hasMorePages(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    /**
     * Estadísticas de juego del jugador — partidas jugadas/ganadas, ganancias
     * totales y logros desbloqueados. Estos accessors/relaciones ya existían
     * en User (usados por ProfileController para el perfil del propio
     * jugador) pero nunca se habían expuesto a ningún admin: soporte/
     * detección de fraude no tenía forma de ver esta información sin acceso
     * directo a BD.
     */
    private function getPlayerGameStats(User $user): array
    {
        $achievements = $user->achievements()
            ->wherePivotNotNull('unlocked_at')
            ->orderByDesc('user_achievements.unlocked_at')
            ->limit(10)
            ->get(['achievements.id', 'achievements.name', 'achievements.icon', 'achievements.badge_color'])
            ->map(fn ($achievement) => [
                'id'          => $achievement->id,
                'name'        => $achievement->name,
                'icon'        => $achievement->icon,
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

        return [
            'games_played'       => $user->games_played_count,
            'games_won'          => $user->games_won_count,
            'total_winnings'     => $user->total_winnings,
            'achievements'       => $achievements,
            'achievements_count' => $user->achievements()->wherePivotNotNull('unlocked_at')->count(),
        ];
    }

    private function mapAuditRow(Audit $audit): array
    {
        return [
            'id'    => (string) $audit->id,
            'date'  => $audit->created_at?->format('d/m/Y H:i:s'),
            'actor' => $audit->user?->full_name ?? 'Sistema',
            'event' => $audit->event,
        ];
    }
}
