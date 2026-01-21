<?php

namespace App\DTOs;

interface ContextDTO
{
    /**
     * Convierte el DTO en un array para la vista.
     *
     * @return array<string, mixed>
     */
    public function toView(): array;
}
