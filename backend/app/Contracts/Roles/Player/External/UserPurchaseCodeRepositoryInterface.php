<?php

namespace App\Contracts\Roles\Player\External;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Roles\Player\External\UserPurchaseCode;
use Illuminate\Database\Eloquent\Collection;

interface UserPurchaseCodeRepositoryInterface extends BaseRepositoryInterface
{
    public function getByPhone(string $phone): Collection;

    public function getByCode(string $purchaseCode): ?UserPurchaseCode;

    public function getActive(): Collection;
}
