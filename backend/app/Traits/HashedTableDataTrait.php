<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use App\Models\Concerns\HasHashedCollection;

trait HashedTableDataTrait
{
    use HasHashedCollection;

    /**
     * Construye las filas con IDs hasheados.
     *
     * @param Collection $rows
     * @param array $rowKeys
     * @return array<int, array<string, mixed>>
     */
    public function buildRows(Collection $rows, array $rowKeys): array
    {
        $hashedIds = $this->hashCollectionIds($rows);

        return $rows->values()->map(function ($row, int $index) use ($rowKeys, $hashedIds) {
            if (!isset($hashedIds[$index])) {
                throw new \LogicException('Desalineación entre filas y hashedIds.');
            }

            $data = [
                'id' => $hashedIds[$index],
                'status' => $row->status ?? '',
            ];

            foreach ($rowKeys as $key) {
                $data[str_replace('.', '_', $key)] = data_get($row, $key);
            }

            return $data;
        })->toArray();
    }

    /**
     * Construye la definición de columnas.
     *
     * @param array $columns Etiquetas de las columnas
     * @param array $rowKeys Llaves asociadas a cada columna (keys del array/fila)
     * @return array<int, array{key:string,label:string,html:bool}>
     */
    public function buildColumns(array $columns, array $rowKeys): array
    {
        return collect($columns)->values()->map(
            fn(string $label, int $index) => [
                'key' => str_replace('.', '_', $rowKeys[$index]),
                'label' => $label,
                'html' => $rowKeys[$index] === 'status_label',
            ]
        )->toArray();
    }

  /**
 * Procesa el contexto para un conjunto de datos con filas y columnas,
 * hashando IDs y construyendo columnas en formato [{key, label}].
 *
 * @param array $context Array de contexto donde están las colecciones
 * @param string $rolePrefix Clave en el contexto con la colección (ej: 'commerces')
 * @param array $columns Etiquetas para las columnas (labels)
 * @param array $rowKeys Llaves para las filas (keys para extraer valores)
 * @return array Contexto modificado con 'columns' y filas actualizadas bajo $rolePrefix
 */
public function processContextTableData(
    array $context,
    string $rolePrefix,
    array $columns,
    array $rowKeys
): array {
    $rows = $context[$rolePrefix] ?? collect();

    // Construir columnas con key y label
    $columnsProcessed = array_map(function ($label, $key) {
        return [
            'key' => $key,
            'label' => $label,
            'html' => $key === 'status_label' // marca la columna status_label para render HTML si usas eso
        ];
    }, $columns, $rowKeys);

    // Construir filas con ids hasheados y keys adecuados
    $rowsProcessed = $this->buildRows($rows, $rowKeys);

    $context['columns'] = $columnsProcessed;
    $context[$rolePrefix] = $rowsProcessed;

    return $context;
}

}
