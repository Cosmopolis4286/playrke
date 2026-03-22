<?php

namespace App\Services\Roles\Admin\User;

use App\Exceptions\Domain\Roles\Admin\User\AdminUserException;
use App\Models\User;
use App\Services\User\UserPasswordService;
use Illuminate\Support\Facades\DB;

final class AdminUserService
{
    public function __construct(
        private readonly AdminUserContextService $context,
        private readonly UserPasswordService     $userPasswordService,
    ) {}

    public function fetchList(): array
    {
        return $this->context->resolveUsers()['users']['rows'] ?? [];
    }

    /**
     * Alternar estado ACTIVE / INACTIVE.
     */
    public function toggleStatus(User $user): void
    {
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
        if ($user->status !== 'ACTIVE') {
            throw AdminUserException::passwordResetRequiresActiveStatus();
        }

        return $this->userPasswordService->resetPassword($user);
    }

    /**
     * Cargar saldo en wallet del usuario.
     */
    public function loadWalletBalance(User $user, float $amount): string
    {
        if ($user->status !== 'ACTIVE') {
            throw AdminUserException::walletLoadRequiresActiveStatus();
        }

        if ($amount <= 0) {
            throw AdminUserException::invalidWalletAmount();
        }

        // TODO: Implementar lógica real de carga de wallet
        // Por ahora, solo retornamos mensaje de éxito
        
        // Aquí iría la lógica para:
        // 1. Verificar si el usuario tiene wallet
        // 2. Crear transacción de carga
        // 3. Actualizar balance
        // 4. Registrar movimiento
        
        return "Saldo cargado exitosamente: $${amount}";
    }
}
