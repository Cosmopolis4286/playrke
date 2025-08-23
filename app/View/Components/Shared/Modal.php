<?php

namespace App\View\Components\Shared;

use Illuminate\View\Component;
use Illuminate\Support\Facades\View;

class Modal extends Component
{
    public string $id;
    public string $title;
    public string $size;
    public ?string $style;
    public ?string $dataForm;
    public array $formData;

    /**
     * Constructor del componente Modal
     *
     * @param string      $id       Identificador único del modal
     * @param string|null $title    Título del modal
     * @param string      $size     Tamaño del modal ('sm', 'md', 'lg', 'big', 'small')
     * @param string|null $style    Clases CSS adicionales
     * @param string|null $formView Ruta de la vista a renderizar dentro del modal
     * @param array       $formData Datos a pasar a la vista del formulario
     */
    public function __construct(
        string $id,
        ?string $title = null,
        string $size = 'md',
        ?string $style = null,
        ?string $formView = null,
        array $formData = []
    ) {
        $this->id = $id;
        $this->title = $title ?? 'SIN TÍTULO';
        $this->size = match ($size) {
            'big'   => 'lg',
            'small' => 'sm',
            default => $size
        };
        $this->style = $style;
        $this->formData = $formData;

        // Renderiza el contenido del formulario solo si la vista existe
        $this->dataForm = ($formView && View::exists($formView))
            ? View::make($formView, $formData)->render()
            : null;
    }

    /**
     * Renderiza la vista del componente
     */
    public function render()
    {
        return view('components.shared.modal');
    }
}
