<?php

namespace App\View\Components\Shared;

use App\View\Components\Concerns\GeneratesHashedIds;
use Illuminate\View\Component;
use Illuminate\Support\Collection;

/**
 * Componente de tabla con selección de filas.
 * Genera IDs encriptados para cada fila usando Hashids y los expone a Alpine.js.
 */
class TableWithSelection extends Component
{
    use GeneratesHashedIds;

    public string $title;
    public string $moduleId;
    public array $columns;
    public Collection $rows;
    public array $rowKeys;
    public string $context;
    public string $tableId;

    /**
     * @var string[] IDs encriptados para Alpine.js
     */
    public array $hashedIds;

    /**
     * Constructor del componente.
     *
     * @param string $title Título de la tabla
     * @param string $moduleId ID del módulo (opcional)
     * @param array $columns Columnas de la tabla
     * @param Collection|null $rows Filas de la tabla
     * @param array $rowKeys Campos clave de cada fila
     * @param string $context Contexto adicional
     * @param string $tableId ID único de la tabla para Alpine
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $title = '',
        string $moduleId = '',
        array $columns = [],
        ?Collection $rows = null,
        array $rowKeys = [],
        string $context = '',
        string $tableId = ''
    ) {
        $this->title = $title;
        $this->moduleId = $moduleId;
        $this->columns = $columns;
        $this->rows = $rows ?? collect();
        $this->rowKeys = $rowKeys;
        $this->context = $context;
        $this->tableId = $tableId;

        $this->validateProps();
        $this->generateHashedIds();
    }

    /**
     * Valida las propiedades requeridas del componente.
     *
     * @throws \InvalidArgumentException
     */
    protected function validateProps(): void
    {
        if (empty($this->columns)) {
            throw new \InvalidArgumentException('Se requiere al menos una columna para TableWithSelection.');
        }

        if (empty($this->rowKeys)) {
            throw new \InvalidArgumentException('Se requiere al menos un rowKey para TableWithSelection.');
        }

        if (empty($this->tableId)) {
            throw new \InvalidArgumentException('Se requiere un tableId único para TableWithSelection.');
        }

        if ($this->rows && !$this->rows instanceof Collection) {
            throw new \InvalidArgumentException('El parámetro rows debe ser una instancia de Collection.');
        }
    }

    /**
     * Genera IDs encriptados para todas las filas usando Hashids (Vinkla).
     * Los IDs se guardan en $hashedIds para usarse en Alpine.js.
     *
     * @return void
     */
    protected function generateHashedIds(): void
    {
        $this->hashedIds = $this->hashCollectionIds($this->rows);
    }

    /**
     * Renderiza la vista del componente.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.shared.table-with-selection');
    }
}
