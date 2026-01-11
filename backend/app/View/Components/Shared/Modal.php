<?php

namespace App\View\Components\Shared;

use Illuminate\View\Component;
use Illuminate\Support\Facades\View;

class Modal extends Component
{
    /**
     * Identificador único del modal
     */
    public string $id;

    /**
     * Título del modal
     */
    public string $title;

    /**
     * Tamaño del modal (sm, md, lg)
     */
    public string $size;

    /**
     * Estilos o atributos adicionales del modal-dialog
     */
    public ?string $style;

    /**
     * Contenido HTML del formulario renderizado
     */
    public ?string $formData;

    /**
     * Boton de confirmacion (submit)
     */
    public ?string $btnConfirm;

    /**
     * Constructor del componente Modal
     *
     * @param string      $id        Identificador único del modal
     * @param string|null $title     Título del modal
     * @param string      $size      Tamaño del modal ('sm', 'md', 'lg', 'big', 'small')
     * @param string|null $style     Clases o atributos adicionales
     * @param string|null $formView  Ruta de la vista Blade a renderizar dentro del modal
     * @param array       $formVars  Variables opcionales para la vista del formulario
     * @param string|null $btnConfirm ID del boton de confirmacion
     */
    public function __construct(
        string $id,
        ?string $title = null,
        string $size = 'md',
        ?string $style = null,
        ?string $formView = null,
        array $formVars = [],
        ?string $btnConfirm = 'btn-confirm'
    ) {
        $this->id = $id;
        $this->title = $title ?? 'SIN TÍTULO';

        $this->size = match ($size) {
            'big'   => 'lg',
            'small' => 'sm',
            default => $size,
        };

        $this->style = $style;

        // Renderiza el formulario solo si la vista existe
        $this->formData = ($formView && View::exists($formView))
            ? View::make($formView, $formVars)->render()
            : null;

        $this->btnConfirm = $btnConfirm;
    }

    /**
     * Renderiza la vista del componente
     */
    public function render()
    {
        return view('components.shared.modal');
    }
}
