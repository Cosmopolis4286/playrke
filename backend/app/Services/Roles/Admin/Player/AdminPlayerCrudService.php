<?php

namespace App\Services\Roles\Admin\Player;

use App\DTOs\Roles\Admin\Player\AdminPlayerDTO;
use App\Exceptions\Domain\Roles\Admin\Player\AdminPlayerException;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio CRUD para Players en contexto Admin.
 *
 * Player = User con rol 'player' (sin tabla propia).
 */
final class AdminPlayerCrudService
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Crear un player (User + rol player).
     *
     * @return string Contraseña generada
     */
    public function create(AdminPlayerDTO $dto): string
    {
        return DB::transaction(function () use ($dto) {
            $plainPassword = bin2hex(random_bytes(4));

            $user = $this->userService->createUserFromDTO($dto, $plainPassword);
            $user->assignRole('player');

            if (! $user) {
                throw AdminPlayerException::creationFailed();
            }

            return $plainPassword;
        });
    }

    /**
     * Obtener player para visualización.
     */
    public function show(User $user): array
    {
        $user->load('country:id,name', 'timeZone:id,name');

        return [
            'id'         => $user->hashed_id,
            'username'   => $user->username,
            'email'      => $user->email,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'phone'      => $user->phone_number,
            'country'    => $user->country ? ['id' => $user->country->id, 'name' => $user->country->name] : null,
            'time_zone_id' => $user->time_zone_id,
        ];
    }

    /**
     * Actualizar datos del player.
     */
    public function update(User $user, AdminPlayerDTO $dto): void
    {
        DB::transaction(function () use ($user, $dto) {
            $data = array_filter([
                'username'     => $dto->username,
                'email'        => $dto->email,
                'first_name'   => $dto->firstName,
                'last_name'    => $dto->lastName,
                'phone_number' => $dto->phone,
                'country_id'   => $dto->country,
                'time_zone_id' => $dto->timeZone,
            ], fn($v) => $v !== null && $v !== '');

            $user->update($data);
        });
    }

    /**
     * Eliminación lógica (soft delete).
     */
    public function delete(User $user): void
    {
        if ($user->status === 'DELETED') {
            throw AdminPlayerException::alreadyDeleted();
        }

        $user->update(['status' => 'DELETED']);
        $user->delete();
    }

    /**
     * Alternar estado ACTIVE / INACTIVE.
     */
    public function toggleStatus(User $user): void
    {
        if ($user->status === 'DELETED') {
            throw AdminPlayerException::invalidStatusChange();
        }

        $user->update([
            'status' => $user->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE',
        ]);
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
}
