<?php

namespace App\Services\Roles\Admin\User;

use App\DTOs\Roles\Admin\User\AdminUserUpdateDTO;
use App\Exceptions\Domain\Roles\Admin\User\AdminUserException;
use App\Models\Role;
use App\Models\User;
use App\Services\User\UserLevelService;
use App\Services\User\UserPasswordService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;

final class AdminUserService
{
    public function __construct(
        private readonly AdminUserContextService $context,
        private readonly UserPasswordService     $userPasswordService,
        private readonly WalletService           $walletService,
        private readonly UserLevelService        $userLevelService,
    ) {}

    public function fetchList(): array
    {
        return $this->context->resolveUsers()['users']['rows'] ?? [];
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

        return $this->userPasswordService->resetPassword($user);
    }

    /**
     * Bloquea auto-modificación y mutación de cuentas superadmin por parte
     * de actores que no lo son (mismo criterio que AdministratorService).
     */
    private function guardMutation(User $user): void
    {
        if ($user->id === auth()->id()) {
            throw AdminUserException::cannotModifySelf();
        }

        if ($user->hasRole('superadmin') && !auth()->user()->hasRole('superadmin')) {
            throw AdminUserException::cannotModifySuperadmin();
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
     * Bloquea asignar el rol superadmin si el actor no lo es.
     */
    private function guardRoleChange(User $user, string $roleId): void
    {
        $role = Role::find($roleId);

        if ($role && strtolower($role->name) === 'superadmin' && !auth()->user()->hasRole('superadmin')) {
            throw AdminUserException::cannotModifySuperadmin();
        }
    }

    /**
     * Roles que pueden recibir carga de saldo manual desde el panel admin.
     */
    private const WALLET_LOAD_ALLOWED_ROLES = ['commerce', 'seller', 'player'];

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

        return $result;
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
}
