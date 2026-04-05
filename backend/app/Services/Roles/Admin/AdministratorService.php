<?php

namespace App\Services\Roles\Admin;

use App\Exceptions\Domain\Roles\Admin\Administrator\AdministratorException;
use App\Models\User;
use App\Services\User\UserPasswordService;
use Illuminate\Support\Facades\DB;

final class adminservice
{
    public function __construct(
        private readonly AdministratorContextService $context,
        private readonly UserPasswordService     $userPasswordService,
    ) {}

    public function fetchList(): array
    {
        return $this->context->resolveadmins()['admins']['rows'] ?? [];
    }

    /**
     * Alternar estado ACTIVE / INACTIVE de administrador.
     */
    public function toggleStatus(User $user): void
    {
        $this->validateAdministrator($user);

        if ($user->status === 'DELETED') {
            throw AdministratorException::invalidStatusChange();
        }

        $user->update([
            'status' => $user->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE',
        ]);
    }

    /**
     * Eliminación lógica (soft delete) de administrador.
     */
    public function delete(User $user): void
    {
        $this->validateAdministrator($user);

        if ($user->status === 'DELETED') {
            throw AdministratorException::alreadyDeleted();
        }

        $user->update(['status' => 'DELETED']);
        $user->delete();
    }

    /**
     * Eliminación definitiva (hard delete) de administrador.
     */
    public function destroy(User $user): void
    {
        $this->validateAdministrator($user);

        // No permitir eliminar al último superadmin
        $superadminCount = User::role('superadmin')->count();
        if ($user->hasRole('superadmin') && $superadminCount <= 1) {
            throw AdministratorException::cannotDeleteLastSuperAdmin();
        }

        $user->forceDelete();
    }

    /**
     * Restablecer contraseña de administrador.
     */
    public function resetPassword(User $user): void
    {
        $this->validateAdministrator($user);

        $newPassword = $this->userPasswordService->generateRandomPassword();
        
        $user->update([
            'password' => bcrypt($newPassword),
            'demo_password' => $newPassword, // Para usuarios demo
        ]);
    }

    /**
     * Validar que el usuario sea un administrador.
     */
    private function validateAdministrator(User $user): void
    {
        if (!$user->hasRole('admin') && !$user->hasRole('superadmin')) {
            throw AdministratorException::notAdministrator();
        }
    }
}
