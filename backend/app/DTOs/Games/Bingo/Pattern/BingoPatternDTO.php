<?php

namespace App\DTOs\Games\Bingo\Pattern;

use App\DTOs\ContextDTO;

final class BingoPatternDTO implements ContextDTO
{
    public function __construct(
        public readonly array $columns,
        public readonly array $rows,
    ) {}

    public function toView(): array
    {
        return [
            'bingoPatterns' => [
                'columns' => $this->columns,
                'rows'    => $this->rows,
            ],
        ];
    }
}
