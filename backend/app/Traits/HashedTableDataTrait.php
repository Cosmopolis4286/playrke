<?php

namespace App\Traits;

use Illuminate\Support\Collection;
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
     */
    protected function processContextTableData(
        array $context,
        string $collectionKey,
        array $labels,
        array $rowKeys
    ): array {
        $rows = $context[$collectionKey] ?? collect();

        $context['columns'] = $this->buildColumns($labels, $rowKeys);
        $context[$collectionKey] = $this->buildRows($rows, $rowKeys);

        return $context;
    }

    /**
     * Construye las filas de la tabla con:
     * - ID hasheado
     * - Keys normalizadas
     */
    protected function buildRows(Collection $rows, array $rowKeys): array
    {
        $hashedIds = $this->hashCollectionIds($rows);

        return $rows->values()->map(function ($row, int $index) use ($rowKeys, $hashedIds) {
            if (!isset($hashedIds[$index])) {
                throw new LogicException('Desalineación entre filas y hashedIds.');
            }

            $data = [
                'id'     => $hashedIds[$index],
                'status' => $row->status ?? null,
            ];

            foreach ($rowKeys as $key) {
                $data[$this->normalizeKey($key)] = data_get($row, $key);
            }

            return $data;
        })->toArray();
    }

    /**
     * Construye definición de columnas para el frontend.
     */
    protected function buildColumns(array $labels, array $rowKeys): array
    {
        return collect($labels)->values()->map(
            fn(string $label, int $index) => [
                'key'   => $this->normalizeKey($rowKeys[$index]),
                'label' => $label,
                'html'  => $rowKeys[$index] === 'status_label',
            ]
        )->toArray();
    }

    /**
     * Normaliza keys:
     * Country.name -> country_name
     * User.email   -> user_email
     */
    protected function normalizeKey(string $key): string
    {
        return strtolower(str_replace('.', '_', $key));
    }
}
