<?php

namespace App\Services\Games\Bingo\Pattern;

use App\Contracts\Games\Bingo\Pattern\BingoPatternRepositoryInterface;
use App\DTOs\Games\Bingo\Pattern\BingoPatternDTO;
use App\Traits\HashedTableDataTrait;
use DomainException;

final class BingoPatternContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly BingoPatternRepositoryInterface $repository,
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
            labels:  ['Nombre', 'Tipo', 'Cajas', 'Por defecto', 'Estado'],
            rowKeys: ['name', 'type_description', 'boxes_count', 'default_pattern', 'status'],
        );

        return new BingoPatternDTO(
            columns: $tableData['columns'],
            rows:    $tableData['rows'],
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
            labels:  ['Nombre', 'Tipo', 'Cajas', 'Por defecto', 'Estado'],
            rowKeys: ['name', 'type_description', 'boxes_count', 'default_pattern', 'status'],
        )['rows'];
    }
}
