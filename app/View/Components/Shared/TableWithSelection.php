<?php

namespace App\View\Components\Shared;

use Illuminate\View\Component;
use Illuminate\Support\Collection;

/**
 * Componente TableWithSelection
 *
 * Renderiza una tabla con selección de filas usando Alpine.js.
 * Incluye un BtnGroup que se habilita/deshabilita según selección.
 *
 * Compatible con Bootstrap 4.
 */
class TableWithSelection extends Component
{
    /** @var string Título del card */
    public string $title;

    /** @var string Identificador único del modulo (usado en BtnGroup) */
    public string $moduleId;

    /** @var array Columnas de la tabla */
    public array $columns;

    /** @var Collection Filas de la tabla */
    public Collection $rows;

    /** @var array Llaves de cada fila para extraer datos */
    public array $rowKeys;

    /** @var string Contexto para BtnGroup */
    public string $context;

    /**
     * Constructor del componente
     *
     * @param string $title Título del card
     * @param string $moduleId Identificador del view
     * @param array $columns Columnas de la tabla
     * @param Collection|null $rows Filas de la tabla
     * @param array $rowKeys Llaves para extraer valores de cada fila
     * @param string $context Contexto para BtnGroup
     */
    public function __construct(
        string $title = '',
        string $moduleId = '',
        array $columns = [],
        ?Collection $rows = null,
        array $rowKeys = [],
        string $context = ''
    ) {
        $this->title = $title;
        $this->moduleId = $moduleId;
        $this->columns = $columns;
        $this->rows = $rows ?? collect();
        $this->rowKeys = $rowKeys;
        $this->context = $context;

        $this->validateProps();
    }

    /**
     * Valida los props del componente
     *
     * @return void
     */
    protected function validateProps(): void
    {
        if (empty($this->columns)) {
            throw new \InvalidArgumentException('Se requiere al menos una columna para TableWithSelection.');
        }

        if (empty($this->rowKeys)) {
            throw new \InvalidArgumentException('Se requiere al menos un rowKey para TableWithSelection.');
        }

        if ($this->rows && !$this->rows instanceof Collection) {
            throw new \InvalidArgumentException('El parámetro rows debe ser una instancia de Collection.');
        }
    }

    /**
     * Renderiza la vista del componente
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('components.shared.table-with-selection');
    }
}
