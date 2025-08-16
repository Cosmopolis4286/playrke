<?php

namespace App\View\Components;

use Illuminate\View\Component;

class BtnGroup extends Component
{
    public $btnGroupId;
    public $dataId;
    public $buttons;

    /**
     * Create a new component instance.
     *
     * @param string $btnGroupId
     * @param mixed $dataId
     * @param array|null $buttons
     */
    public function __construct($btnGroupId, $dataId, $buttons = null)
    {
        $this->btnGroupId = $btnGroupId;
        $this->dataId = $dataId;

        // Obtiene los botones por defecto
        $defaultButtons = $this->defaultButtons();

        // Si se pasan botones adicionales, los anexamos al final
        if (is_array($buttons) && count($buttons) > 0) {
            $this->buttons = array_merge($defaultButtons, $buttons);
        } else {
            $this->buttons = $defaultButtons;
        }
    }

    /**
     * Genera un conjunto de botones por defecto
     *
     * @return array
     */
    protected function defaultButtons()
    {
        return [
            (object)[
                'action' => 'edit',
                'color' => 'btn-primary',
                'icon'  => 'fas fa-edit',
                'title' => 'Editar',
            ],
            (object)[
                'action' => 'delete',
                'color' => 'btn-danger',
                'icon'  => 'fa-solid fa-trash-can',
                'title' => 'Eliminar',
            ]
        ];
    }

    public function render()
    {
        return view('components.btn-group');
    }
}
