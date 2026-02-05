<?php

namespace App\View\Components\Shared;

use Illuminate\View\Component;
use Illuminate\Support\Collection;
use App\Models\Concerns\HasHashedCollection;

/**
 * Componente de tabla con filtros basado en Vue.
 *
 * Responsabilidades:
 * - Consumir IDs hasheados desde los modelos (Hashids)
 * - Transformar filas y columnas a un contrato estable para Vue
 * - Garantizar que el frontend NUNCA vea IDs reales
 * - Actuar como bridge entre Laravel (Blade) y Vue
 *
 * ⚠️ La vista Blade es completamente pasiva.
 */
class TableWithFilterVue extends Component
{
    use HasHashedCollection;

    /** Título de la tabla */
    public string $title;

    /** ID del módulo asociado (CRUD, modal, etc.) */
    public string $moduleId;

    /** Contexto funcional (users, commerces, sellers, …) */
    public string $context;

    /** ID único de la tabla (scoping JS / Vue) */
    public string $tableId;

    /** Placeholder del buscador */
    public string $searchPlaceholder;

    /** Mostrar botón crear */
    public bool $showCreateButton;

    /** Mostrar botón editar */
    public bool $showEditButton;

    /** Mostrar botón eliminar */
    public bool $showDeleteButton;

    /**
     * Filas finales consumidas por Vue
     *
     * @var array<int, array<string, mixed>>
     */
    public array $vueRows;

    /**
     * Definición de columnas para Vue
     *
     * @var array<int, array{key:string,label:string,html:bool}>
     */
    public array $vueColumns;

    /**
     * Definición de filtros para Vue
     *
     * @var array<int, array<string, mixed>>
     */
    public array $filters;

    /**
     * Botones adicionales para la interfaz, enviados al frontend como configuración.
     *
     * Cada botón es un array con keys como:
     * - label: string (texto visible)
     * - icon?: string (clases para icono, ej: 'fas fa-info-circle')
     * - class?: string (clases CSS para el botón, ej: 'btn-info')
     *
     * No contiene lógica, solo configuración para renderizado en Vue.
     *
     * @var array<int, array<string, string>>
     */
    public array $extraButtons;

    /**
     * Constructor.
     *
     * @param string           $title
     * @param string           $moduleId
     * @param array            $columns
     * @param Collection|null  $rows
     * @param array            $rowKeys
     * @param string           $context
     * @param string           $tableId
     * @param array            $filters
     * @param string           $searchPlaceholder
     * @param bool             $showCreateButton
     * @param bool             $showEditButton
     * @param bool             $showDeleteButton
     * @param array            $extraButtons
     */
    public function __construct(
        string $title = '',
        string $moduleId = '',
        array $columns = [],
        ?Collection $rows = null,
        array $rowKeys = [],
        string $context = '',
        string $tableId = '',
        array $filters = [],
        string $searchPlaceholder = 'Buscar en la tabla...',
        bool $showCreateButton = true,
        bool $showEditButton = true,
        bool $showDeleteButton = true,
        array $extraButtons = []
    ) {
        $rows ??= collect();

        $this->validateProps($columns, $rowKeys, $tableId);

        $this->title = $title;
        $this->moduleId = $moduleId;
        $this->context = $context;
        $this->tableId = $tableId;
        $this->filters = $filters;
        $this->searchPlaceholder = $searchPlaceholder;
        $this->showCreateButton = $showCreateButton;
        $this->showEditButton = $showEditButton;
        $this->showDeleteButton = $showDeleteButton;
        $this->extraButtons = $extraButtons;

        /**
         * Hashes alineados por índice.
         * Usan cache global por request.
         */
        $hashedIds = $this->hashCollectionIds($rows);

        $this->vueRows = $this->buildVueRows($rows, $rowKeys, $hashedIds);
        $this->vueColumns = $this->buildVueColumns($columns, $rowKeys);
    }

    /**
     * Validación estructural del componente.
     *
     * @param array $columns
     * @param array $rowKeys
     * @param string $tableId
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function validateProps(
        array $columns,
        array $rowKeys,
        string $tableId
    ): void {
        if (empty($columns)) {
            throw new \InvalidArgumentException(
                'TableWithFilterVue requiere al menos una columna.'
            );
        }

        if (empty($rowKeys)) {
            throw new \InvalidArgumentException(
                'TableWithFilterVue requiere al menos un rowKey.'
            );
        }

        if (count($columns) !== count($rowKeys)) {
            throw new \InvalidArgumentException(
                'columns y rowKeys deben tener la misma longitud.'
            );
        }

        if (empty($tableId)) {
            throw new \InvalidArgumentException(
                'TableWithFilterVue requiere un tableId único.'
            );
        }
    }

    /**
     * Construye las filas finales para Vue.
     *
     * - ID siempre hasheado
     * - Normaliza claves nested (User.email → User_email)
     * - No existe fallback a ID real
     *
     * @param Collection $rows
     * @param array $rowKeys
     * @param array $hashedIds
     * @return array<int, array<string, mixed>>
     */
    protected function buildVueRows(
        Collection $rows,
        array $rowKeys,
        array $hashedIds
    ): array {
        return $rows->values()->map(function ($row, int $index) use ($rowKeys, $hashedIds) {
            if (!isset($hashedIds[$index])) {
                throw new \LogicException(
                    'Desalineación entre filas y hashedIds.'
                );
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
     * Construye la definición de columnas para Vue.
     *
     * @param array $columns
     * @param array $rowKeys
     * @return array<int, array{key:string,label:string,html:bool}>
     */
    protected function buildVueColumns(array $columns, array $rowKeys): array
    {
        return collect($columns)->values()->map(
            fn(string $label, int $index) => [
                'key'   => str_replace('.', '_', $rowKeys[$index]),
                'label' => $label,
                'html'  => $rowKeys[$index] === 'status_badge',
            ]
        )->toArray();
    }

    /**
     * Renderiza la vista Blade asociada.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('components.shared.table-with-filter-vue');
    }
}
