<?php

namespace App\Services\Games\Bingo\Pattern;

use App\Contracts\Games\Bingo\Pattern\BingoPatternRepositoryInterface;
use App\DTOs\Games\Bingo\Pattern\BingoPatternDTO;
use App\Services\Roles\RoleUserService;
use App\Traits\HashedTableDataTrait;
use DomainException;

final class BingoPatternContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly BingoPatternRepositoryInterface $repository,
        private readonly RoleUserService $roleUserService,
    ) {}

    /**
     * Resolver contexto para BingoPattern.
     */
    public function context(string $context, ?int $userId): mixed
    {
        return match ($context) {
            'index' => $this->resolve($userId),
            'fetch' => $this->fetchList($userId),
            default => throw new DomainException('No existe el contexto: ' . $context),
        };
    }

    private function resolve(?int $userId): BingoPatternDTO
    {
        $patterns = $this->repository->getByUserAndDefault((string) $userId);

        $tableData = $this->processCollectionTableData(
            $patterns,
            labels: ['Nombre', 'Tipo', 'Casillas', 'Estado'],
            rowKeys: ['name', 'type_description', 'boxes_count', 'status_badge'],
        );

        $userRolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

        return new BingoPatternDTO(
            columns: $tableData['columns'],
            rows: $tableData['rows'],
            roleUser: $userRolePrefix,
        );
    }

    /**
     * Retorna lista de patrones para el usuario (usado en fetchList).
     */
    public function fetchList(?int $userId): array
    {
        $patterns = $this->repository->getByUserAndDefault((string) $userId);

        return $this->processCollectionTableData(
            $patterns,
            labels: ['Nombre', 'Tipo', 'Casillas', 'Por defecto', 'Estado'],
            rowKeys: ['name', 'type_description', 'boxes_count', 'default_pattern', 'status'],
        )['rows'];
    }
}
