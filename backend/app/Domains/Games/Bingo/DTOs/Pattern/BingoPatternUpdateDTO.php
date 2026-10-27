<?php

namespace App\Domains\Games\Bingo\DTOs\Pattern;

/**
 * Data Transfer Object para la actualización de un Patrón de Bingo.
 *
 * Campos del modelo bingo_patterns:
 *   - name           string (15)
 *   - type_pattern   UNIQUE | MULTIPLE
 *   - default_pattern Y | N
 *   - boxes          string separado por ';'
 */
final class BingoPatternUpdateDTO
{
    public static function fromArray(array $data) : self
    {
        return new self(
            name: trim($data['name']),
            typePattern: $data['type_pattern'],
            boxes: $data['boxes'],
            defaultPattern: $data['default_pattern'] ?? 'N',
        );
    }

    public function __construct(
        public readonly string $name,
        public readonly string $typePattern,
        public readonly string $boxes,
        public readonly string $defaultPattern,
    ) {
    }
}
