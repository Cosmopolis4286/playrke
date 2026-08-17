<?php

namespace App\Services\Roles\Admin;

use App\Contracts\UserRepositoryInterface;
use App\DTOs\Roles\Admin\Administrator\AdministratorStoreDTO;
use App\DTOs\Roles\Admin\Administrator\AdministratorUpdateDTO;
use App\Exceptions\Domain\Roles\Admin\Administrator\AdministratorException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AdministratorService
{
    public function __construct(
        private readonly AdministratorContextService $context,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function fetchList(array $filters = [], int $page = 1): array
    {
        return $this->context->resolveadmins($filters, $page);
    }

    /**
     * Alta de un nuevo administrador (rol 'admin', nunca 'superadmin' — un
     * superadmin adicional solo se crea por vía directa de base de datos /
     * seeder, no desde este flujo de UI).
     */
    public function store(AdministratorStoreDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $user = User::create([
                'first_name'   => $dto->firstName,
                'last_name'    => $dto->lastName,
                'username'     => $dto->username,
                'email'        => $dto->email,
                'password'     => Hash::make($dto->password),
                'country_id'   => $dto->countryId,
                'phone_number' => $dto->phoneNumber,
                'status'       => 'ACTIVE',
                'email_verified_at' => now(),
                'referral_code' => $this->users->generateUniqueReferralCode(),
            ]);

            $user->assignRole('admin');

            return $user;
        });
    }

    /**
     * Actualizar datos básicos de un administrador existente. A diferencia
     * del resto de acciones de este service, SÍ permite auto-edición (un
     * superadmin puede editar su propia ficha) — AdministratorUpdateDTO no
     * incluye `status`, así que esta vía nunca puede auto-desactivar al
     * actor ni a nadie más (ver toggleStatus() para eso).
     */
    public function update(User $user, AdministratorUpdateDTO $dto): void
    {
        $this->validateIsAdministrator($user);

        $user->update($dto->toArray());
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

        // Si se va a desactivar (no reactivar), no puede ser el último
        // admin/superadmin activo del sistema: dejaría el panel sin nadie
        // que pueda reactivar cuentas.
        if ($user->status === 'ACTIVE') {
            $this->guardNotLastActiveAdministrator($user);
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
        $this->guardNotLastActiveAdministrator($user);

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
        $this->guardNotLastActiveAdministrator($user);

        // No permitir eliminar al último superadmin — regla adicional a
        // guardNotLastActiveAdministrator(): un admin normal SÍ puede quedar
        // en cero (el sistema sigue teniendo superadmins), pero el rol
        // 'superadmin' en sí nunca puede quedarse sin representantes, ya que
        // es la única cuenta que puede reactivar cualquier otra.
        $superadminCount = User::role('superadmin')->count();
        if ($user->hasRole('superadmin') && $superadminCount <= 1) {
            throw AdministratorException::cannotDeleteLastSuperAdmin();
        }

        DB::transaction(function () use ($user) {
            $user->roles()->detach();
            $user->forceDelete();
        });
    }

    /**
     * Restablecer contraseña de administrador. Exige que la cuenta esté
     * ACTIVE — mismo criterio que AdminUserService::resetPassword(), evita
     * generar credenciales nuevas para una cuenta suspendida/eliminada.
     */
    public function resetPassword(User $user): string
    {
        $this->validateAdministrator($user);

        if ($user->status !== 'ACTIVE') {
            throw AdministratorException::passwordResetRequiresActiveStatus();
        }

        $newPassword = Str::random(12);

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return $newPassword;
    }

    /**
     * Validar que el usuario sea realmente un administrador (admin o
     * superadmin), sin restricción de auto-modificación — usado por update(),
     * la única acción que un superadmin puede aplicarse a sí mismo.
     */
    private function validateIsAdministrator(User $user): void
    {
        if (!$user->hasRole('admin') && !$user->hasRole('superadmin')) {
            throw AdministratorException::notAdministrator();
        }
    }

    /**
     * Validar que el usuario sea un administrador y que el actor no se esté
     * aplicando a sí mismo una acción sensible (status/password/delete).
     * Reasegura a nivel de service lo que UserPolicy::manageAdministrator()
     * ya bloquea, para que ningún otro caller que invoque el service
     * directamente pueda saltarse la regla.
     */
    private function validateAdministrator(User $user): void
    {
        $this->validateIsAdministrator($user);

        if ($user->id === auth()->id()) {
            throw AdministratorException::cannotModifySelf();
        }
    }

    /**
     * Bloquea desactivar/eliminar al último administrador (admin O
     * superadmin, cualquiera) que quedaría ACTIVE en el sistema — sin esto,
     * sería posible desactivar en cascada a todos los admins y quedar sin
     * ninguna cuenta capaz de gestionar el panel.
     */
    private function guardNotLastActiveAdministrator(User $user): void
    {
        if ($user->status !== 'ACTIVE') {
            return;
        }

        $activeAdminCount = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'superadmin']))
            ->where('status', 'ACTIVE')
            ->count();

        if ($activeAdminCount <= 1) {
            throw AdministratorException::cannotRemoveLastActiveAdministrator();
        }
    }
}
