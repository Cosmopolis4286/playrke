<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Concerns\HasHashedCollection;
use LogicException;

trait HashedTableDataTrait
{
    use HasHashedCollection;

    /**
     * Procesa el contexto para tablas Inertia:
     * - Hashea IDs
     * - Normaliza keys (snake_case)
     * - Construye columnas [{ key, label, html }]
     *
     * @param array  $context
     * @param string $collectionKey  Clave de la colección en el contexto (ej: 'commerces')
     * @param array  $labels         Labels visibles de columnas
     * @param array  $rowKeys        Keys fuente (ej: Country.name)
     *
     * @return array Contexto modificado listo para pasar a la vista
     *
     * @throws LogicException Si labels y rowKeys no coinciden en cantidad
     */
    protected function processContextTableData(
        array $context,
        string $collectionKey,
        array $labels,
        array $rowKeys
    ): array {
        if (count($labels) !== count($rowKeys)) {
            throw new LogicException('Labels y rowKeys deben tener la misma cantidad de elementos.');
        }

        $rows = $context[$collectionKey] ?? collect();

        // Soportar paginadores además de colecciones
        if ($rows instanceof LengthAwarePaginator) {
            $collection = $rows->getCollection();
        } elseif ($rows instanceof Collection) {
            $collection = $rows;
        } else {
            $collection = collect($rows);
        }

        $context['columns'] = $this->buildColumns($labels, $rowKeys);
        $context[$collectionKey] = $this->buildRows($collection, $rowKeys);

        // Si venía paginación, incluir los datos extra
        if ($rows instanceof LengthAwarePaginator) {
            $context["{$collectionKey}_pagination"] = [
                'total' => $rows->total(),
                'per_page' => $rows->perPage(),
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'from' => $rows->firstItem(),
                'to' => $rows->lastItem(),
            ];
        }

        return $context;
    }

    /**
     * Construye las filas de la tabla con:
     * - ID hasheado
     * - Keys normalizadas
     *
     * @param Collection $rows Colección de modelos
     * @param array $rowKeys Keys para extraer datos
     *
     * @return array Filas formateadas para la tabla
     *
     * @throws LogicException Si cantidad de filas y IDs hasheados no coinciden
     */
    protected function buildRows(Collection $rows, array $rowKeys): array
    {
        $hashedIds = $this->hashCollectionIds($rows);

        if ($rows->count() !== count($hashedIds)) {
            throw new LogicException('Cantidad de filas e IDs hasheados no coincide.');
        }

        return $rows->values()->map(
            fn($row, int $index) => $this->buildRow($row, $rowKeys, $hashedIds[$index])
        )->all();
    }

    /**
     * Construye una fila individual normalizada.
     *
     * @param mixed $row Modelo o array de datos
     * @param array $rowKeys Keys para extraer datos
     * @param string $hashedId ID hasheado de la fila
     *
     * @return array Fila formateada
     */
    protected function buildRow(mixed $row, array $rowKeys, string $hashedId): array
    {
        $data = [
            'id'     => $hashedId,
            'status' => $row->status ?? null,
        ];

        foreach ($rowKeys as $key) {
            $data[$this->normalizeKey($key)] = data_get($row, $key);
        }

        return $data;
    }

    /**
     * Construye definición de columnas para el frontend.
     *
     * @param array $labels Labels visibles
     * @param array $rowKeys Keys fuente
     *
     * @return array Columnas definidas para la tabla
     */
    protected function buildColumns(array $labels, array $rowKeys): array
    {
        return collect($labels)->values()->map(
            fn(string $label, int $index) => [
                'key'   => $this->normalizeKey($rowKeys[$index]),
                'label' => $label,
                'html'  => $rowKeys[$index] === 'status_label',
            ]
        )->all();
    }

    /**
     * Normaliza keys:
     * Country.name -> country_name
     * User.email   -> user_email
     *
     * @param string $key Key original
     * @return string Key normalizada en snake_case
     */
    protected function normalizeKey(string $key): string
    {
        return strtolower(str_replace('.', '_', $key));
    }
}
