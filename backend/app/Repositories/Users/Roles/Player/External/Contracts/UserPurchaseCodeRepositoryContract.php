<?php

namespace App\Repositories\Users\Roles\Player\External\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;

/**
 * Contrato para el repositorio de UserPurchaseCode.
 */
interface UserPurchaseCodeRepositoryContract extends BaseRepositoryContract
{
    public function getByUser(string $userId);
    public function getByCode(string $code);
    public function getActive();
}
