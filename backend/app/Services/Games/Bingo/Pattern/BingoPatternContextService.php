<?php

namespace App\Services\Games\Bingo\Pattern;

use App\Domains\Games\Bingo\Contracts\Pattern\BingoPatternRepositoryInterface;
use App\Domains\Games\Bingo\DTOs\Pattern\BingoPatternDTO;
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

        // Inyectar default_pattern en cada fila como metadato oculto.
        // No forma parte de las columnas visibles pero el frontend lo necesita
        // para controlar permisos (botones Editar/Eliminar/Visualizar).
        $tableData['rows'] = array_map(function (array $row, int $i) use ($patterns) {
            $row['default_pattern'] = $patterns->values()->get($i)?->default_pattern ?? 'N';
            return $row;
        }, $tableData['rows'], array_keys($tableData['rows']));

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
