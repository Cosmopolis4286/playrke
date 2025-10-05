<?php

namespace App\Repositories\Users\Shared\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;

/**
 * Contrato para el repositorio de UniqueCode.
 */
interface UniqueCodeRepositoryContract extends BaseRepositoryContract
{
    public function getByCode(string $code);
    public function generateUniqueCode(): string;
    public function isCodeUnique(string $code): bool;
}