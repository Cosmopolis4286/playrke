<?php

namespace App\Contracts\Shared;

use App\Contracts\BaseRepositoryInterface;

/**
 * Contrato para el repositorio de UniqueCode.
 */
interface UniqueCodeRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCode(string $code);
    public function generateUniqueCode(): string;
    public function isCodeUnique(string $code): bool;
}
