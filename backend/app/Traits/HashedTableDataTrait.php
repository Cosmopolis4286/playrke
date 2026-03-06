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
     * Procesa una colección directamente para tablas Inertia:
     * - Hashea IDs
     * - Normaliza keys
     * - Construye columnas y filas
     *
     * @param Collection $collection Colección de modelos
     * @param array $labels Labels visibles de columnas
     * @param array $rowKeys Keys fuente (ej: Country.name)
     *
     * @return array ['columns' => [], 'rows' => []]
     *
     * @throws LogicException Si labels y rowKeys no coinciden en cantidad
     */
    protected function processCollectionTableData(
        Collection $collection,
        array $labels,
        array $rowKeys
    ): array {
        if (count($labels) !== count($rowKeys)) {
            throw new LogicException('Labels y rowKeys deben tener la misma cantidad de elementos.');
        }

        return [
            'columns' => $this->buildColumns($labels, $rowKeys),
            'rows'    => $this->buildRows($collection, $rowKeys),
        ];
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
            'status' => $this->resolveRowStatus($row),
        ];

        foreach ($rowKeys as $key) {
            $normalizedKey = $this->normalizeKey($key);

            if (in_array($normalizedKey, ['id', 'status'], true)) {
                continue;
            }

            $data[$normalizedKey] = data_get($row, $key);
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
                'html'  => str_ends_with($rowKeys[$index], '_badge'),
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

    /** Resuelve el valor de status para una fila, soportando distintos formatos:
     * - status directo (string)
     * - status.value (relación)
     * - status_id (fallback)
     *
     * @param mixed $row Fila de datos (modelo o array)
     * @return string|null Valor del status o null si no se encuentra
     */
    protected function resolveRowStatus(mixed $row): mixed
    {
        // Si es array
        if (is_array($row)) {
            return $row['status']
                ?? $row['status_value']
                ?? $row['status_id']
                ?? null;
        }

        // Si tiene accessor status_value (como BingoRoom)
        if (isset($row->status_value)) {
            return $row->status_value;
        }

        // Si existe el atributo status (como Commerce)
        if (isset($row->status) && is_string($row->status)) {
            return $row->status;
        }

        // Si status es relación (objeto)
        if (isset($row->status) && is_object($row->status)) {
            return $row->status->value
                ?? $row->status->name
                ?? $row->status->description
                ?? null;
        }

        // Fallback: status_id
        if (isset($row->status_id)) {
            return $row->status_id;
        }

        return null;
    }
}
