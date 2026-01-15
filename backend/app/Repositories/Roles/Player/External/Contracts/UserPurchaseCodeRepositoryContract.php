<?php

namespace App\Repositories\Roles\Player\External\Contracts;

use App\Contracts\BaseRepositoryInterface;

/**
 * Contrato para el repositorio de UserPurchaseCode.
 */
interface UserPurchaseCodeRepositoryContract extends BaseRepositoryInterface
{
    public function getByUser(string $userId);
    public function getByCode(string $code);
    public function getActive();
}
