<?php

namespace App\Repositories\Shared\Contracts;

use App\Contracts\BaseRepositoryInterface;

/**
 * Contrato para el repositorio de UniqueCode.
 */
interface UniqueCodeRepositoryContract extends BaseRepositoryInterface
{
    public function getByCode(string $code);
    public function generateUniqueCode(): string;
    public function isCodeUnique(string $code): bool;
}