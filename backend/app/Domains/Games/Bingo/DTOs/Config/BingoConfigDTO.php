<?php

namespace App\Domains\Games\Bingo\DTOs\Config;

use App\DTOs\ContextDTO;

final class BingoConfigDTO implements ContextDTO
{
    public function __construct(
        public readonly string $imageBingoRules,
        public readonly string $bingoRules
    ) {}

    public function toView(): array
    {
        return [
            'imageBingoRules' => $this->imageBingoRules ?? '',
            'bingoRules' => $this->bingoRules
        ];
    }
}
