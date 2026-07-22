<?php

namespace App\Services\Roles\Admin;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

final class RoleService
{
    public function getAll(): Collection
    {
        return Role::orderBy('name')->get();
    }

    /**
     * Actualiza solo metadatos editables (description). El campo `name` es
     * la clave técnica que consumen Spatie, el middleware `role:` y
     * SidebarMenuService::exclude_roles — no se expone a edición aquí para
     * no romper autorización en cascada por un typo.
     */
    public function updateDescription(string $roleId, string $description): Role
    {
        $role = Role::findOrFail($roleId);
        $role->update(['description' => $description]);

        return $role;
    }

    /**
     * Excluye siempre el rol 'superadmin' — reasegura a nivel de service lo
     * que RolePolicy::update() ya bloquea, para que ningún otro caller que
     * invoque el service directamente (ej. un comando artisan futuro) pueda
     * desactivar accidentalmente la cuenta raíz de autorización.
     */
    public function toggleStatus(string $roleId): Role
    {
        $role = Role::findOrFail($roleId);

        if ($role->name === 'superadmin') {
            throw new \RuntimeException('El rol superadmin no puede desactivarse.');
        }

        $role->update(['status' => ! $role->status]);

        return $role;
    }
}
