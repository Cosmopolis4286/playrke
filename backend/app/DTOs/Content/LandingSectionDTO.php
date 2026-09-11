<?php

namespace App\DTOs\Content;

/**
 * DTO minimalista: update() de este módulo SOLO toca `content` — status y
 * sort_order tienen sus propios endpoints dedicados (toggleStatus, reorder)
 * para que guardar un texto nunca arrastre sin querer un cambio de orden o
 * visibilidad.
 */
final class LandingSectionDTO
{
    public function __construct(public readonly array $content) {}

    public static function fromArray(array $data): self
    {
        return new self(content: $data['content']);
    }

    public function toArray(): array
    {
        return ['content' => $this->content];
    }
}
