<?php

namespace App\View\Components\Shared;

use Illuminate\View\Component;

/**
 * Componente BtnGroup
 *
 * Renderiza un grupo de botones de acción, con soporte para:
 * - Botones por defecto (Crear, Editar, Eliminar)
 * - Botones adicionales según contexto (`config/buttons.php`)
 * - Botones enviados manualmente
 * - IDs seleccionados para acciones dinámicas
 *
 * Compatible con Bootstrap 4.
 */
class BtnGroup extends Component
{
    /** Identificador único del grupo de botones */
    public string $btnGroupId;

    /** @var object[] Botones finales a renderizar */
    public array $buttons;

    /** Valor asignado al atributo `data-id` en HTML (puede ser número o array de ids) */
    public mixed $dataId;

    /** @var int[] IDs seleccionados, usados para habilitar/deshabilitar botones */
    public array $selectedIds;

    /**
     * Constructor del componente BtnGroup
     *
     * @param string      $btnGroupId  Identificador único del grupo de botones
     * @param mixed       $dataId      Valor asignado a data-id
     * @param string|null $context     Contexto para cargar botones extra desde config
     * @param array|null  $buttons     Botones adicionales manuales (array u objeto)
     * @param array       $selectedIds IDs seleccionados para habilitar/deshabilitar botones
     */
    public function __construct(
        string $btnGroupId,
        mixed $dataId,
        ?string $context = null,
        ?array $buttons = null,
        array $selectedIds = [],
    ) {
        $this->btnGroupId = $btnGroupId;
        $this->dataId = $dataId;
        $this->selectedIds = $selectedIds;

        // 🔹 Botones por defecto
        $defaultButtons = $this->defaultButtons();

        // 🔹 Botones extra desde config según contexto
        $extraButtons = $context ? (config("buttons.$context") ?? []) : [];

        // 🔹 Fusionar botones y normalizar
        $merged = array_merge($defaultButtons, $extraButtons, $buttons ?? []);
        $this->buttons = array_map(fn ($b) => $this->normalizeButton($b), $merged);
    }

    /**
     * Renderiza la vista del componente
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('components.shared.btn-group');
    }

    /**
     * Botones por defecto (Bootstrap 4)
     *
     * @return array<object>
     */
    protected function defaultButtons() : array
    {
        return [
            (object) [
                'action' => 'create',
                'color' => 'btn-success',
                'disabled' => false,
                'icon' => 'fas fa-plus-square',
                'title' => 'Crear',
                'titleIcon' => 'Crear',
            ],
            (object) [
                'action' => 'edit',
                'color' => 'btn-primary',
                'disabled' => true,
                'icon' => 'fas fa-edit',
                'title' => 'Editar',
            ],
            (object) [
                'action' => 'delete',
                'color' => 'btn-danger',
                'disabled' => true,
                'icon' => 'fa fa-trash',
                'title' => 'Eliminar',
            ],
        ];
    }

    /**
     * Normaliza un botón array|objeto a objeto consistente
     */
    protected function normalizeButton(array|object $button) : object
    {
        $btn = is_array($button) ? (object) $button : $button;

        // Determinar si el botón debe deshabilitarse según selectedIds
        if (in_array($btn->action, ['edit', 'delete', 'changeStatus'])) {
            $btn->disabled = empty($this->selectedIds);
        }

        return (object) [
            'action' => $btn->action ?? '',
            'color' => $btn->color ?? 'btn-secondary',
            'icon' => $btn->icon ?? 'fa fa-circle',
            'title' => $btn->title ?? 'Opción',
            'titleIcon' => $btn->titleIcon ?? null,
            'disabled' => $btn->disabled ?? false,
            'status' => $btn->status ?? null,
        ];
    }
}
