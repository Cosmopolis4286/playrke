<?php

namespace App\Services\Roles\Admin\Player;

use App\DTOs\Roles\Admin\Player\AdminPlayerDTO;
use App\Exceptions\Domain\Roles\Admin\Player\AdminPlayerException;
use App\Models\User;
use App\Services\User\UserPasswordService;
use Illuminate\Support\LazyCollection;

/**
 * Servicio de aplicación para casos de uso del dominio AdminPlayer.
 */
final class AdminPlayerService
{
    public function __construct(
        private readonly AdminPlayerContextService $context,
        private readonly AdminPlayerCrudService    $crud,
        private readonly UserPasswordService       $userPasswordService,
    ) {}

    /**
     * $filters acepta: search, country_name, status — ver
     * AdminPlayerContextService::buildFilteredQuery().
     */
    public function fetchList(array $filters = [], int $page = 1): array
    {
        $data = $this->context->resolvePlayers($filters, $page);

        return [
            'rows'       => $data['players']['rows'] ?? [],
            'pagination' => $data['pagination'],
        ];
    }

    /**
     * Cursor sobre TODOS los jugadores que coincidan con los filtros (sin
     * paginar) — mismo patrón que AdminUserService::exportForCsv().
     *
     * @return LazyCollection<int, User>
     */
    public function exportForCsv(array $filters = []): LazyCollection
    {
        return $this->context->exportForCsv($filters);
    }

    public function create(AdminPlayerDTO $dto): string
    {
        return $this->crud->create($dto);
    }

    public function show(User $user): array
    {
        return $this->crud->show($user);
    }

    public function update(User $user, AdminPlayerDTO $dto): void
    {
        $this->crud->update($user, $dto);
    }

    public function delete(User $user): void
    {
        $this->crud->delete($user);
    }

    public function toggleStatus(User $user): void
    {
        $this->crud->toggleStatus($user);
    }

    public function toggleStatusBulk(array $userIds): array
    {
        return $this->crud->toggleStatusBulk($userIds);
    }

    public function deleteBulk(array $userIds): array
    {
        return $this->crud->deleteBulk($userIds);
    }

    public function destroy(User $user): void
    {
        $this->crud->destroy($user);
    }

    public function resetPassword(User $user): string
    {
        if ($user->status !== 'ACTIVE') {
            throw AdminPlayerException::passwordResetRequiresActiveStatus();
        }

        return $this->userPasswordService->resetPassword($user);
    }
}
