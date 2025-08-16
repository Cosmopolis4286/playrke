<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\View;

class Modal extends Component
{
    public $id;
    public $title;
    public $size;
    public $style;
    public $dataForm; // contenido renderizado de formView o null

    public function __construct(
        $id,
        $title = null,
        $size = 'md',
        $style = null,
        $formView = null
    ) {
        $this->id = $id;

        // Título siempre en mayúsculas
        $this->title = $title
            ? strtoupper($title)
            : 'SIN TÍTULO';

        // Tamaño
        $this->size = match ($size) {
            'big' => 'lg',
            'small' => 'sm',
            default => $size
        };

        $this->style = $style;

        // Renderiza la vista del form si existe
        if ($formView && View::exists($formView)) {
            $this->dataForm = View::make($formView)->render();
        } else {
            $this->dataForm = null;
        }
    }

    public function render()
    {
        return view('components.shared.modal');
    }
}
