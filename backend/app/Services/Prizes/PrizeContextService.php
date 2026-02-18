<?php

namespace App\Services\Prizes;

use App\Contracts\PrizeRepositoryInterface;
use App\Traits\HashedTableDataTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de contexto para la página de Premios.
 *
 * Responsabilidades:
 * - Resolver datos necesarios para la vista (tabla + filtros)
 * - Formatear datos al formato estándar de tabla
 */
final class PrizeContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly PrizeRepositoryInterface $prizes,
    ) {}

    /**
     * Resolver contexto completo para la página de premios.
     */
    public function prizeContext(): array
    {
        $prizeData = $this->resolvePrizes();

        return [
            'prizes'            => $prizeData['prizes'],
            'prizes_pagination' => $prizeData['pagination'],
        ];
    }

    /**
     * Resolver listado de premios paginado.
     */
    public function resolvePrizes(): array
    {
        $labels  = ['Código', 'Nombre', 'Tipo', 'Kit', 'Ítems', 'Precio', 'Estado'];
        $rowKeys = ['code', 'name', 'reward_type', 'kit', 'num_items', 'price', 'status_badge'];

        $source = $this->prizes->getPaginatedPrizes(
            perPage: 15,
            filters: ['user_id' => Auth::id()]
        );

        $collection = $source instanceof LengthAwarePaginator
            ? $source->getCollection()
            : collect($source);

        $pagination = $source instanceof LengthAwarePaginator
            ? [
                'total'        => $source->total(),
                'per_page'     => $source->perPage(),
                'current_page' => $source->currentPage(),
                'last_page'    => $source->lastPage(),
                'from'         => $source->firstItem(),
                'to'           => $source->lastItem(),
            ]
            : null;

        $prizes = $this->processCollectionTableData($collection, $labels, $rowKeys);

        return ['prizes' => $prizes, 'pagination' => $pagination];
    }

    /**
     * Obtener lista plana de premios activos para selects.
     */
    public function fetchList(): array
    {
        return $this->prizes
            ->getPaginatedPrizes(perPage: 200, filters: ['user_id' => Auth::id()])
            ->getCollection()
            ->toArray();
    }
}
