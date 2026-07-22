<?php

namespace App\Repositories\Roles\Player\External;

use App\Contracts\Roles\Player\External\UserPurchaseCodeRepositoryInterface;
use App\Models\Roles\Player\External\UserPurchaseCode;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para UserPurchaseCode (código de compra generado junto a cada
 * ExternalPlayer nuevo — ver PlayerLinkTrait::handleExternalPlayers()).
 */
class UserPurchaseCodeRepository extends BaseRepository implements UserPurchaseCodeRepositoryInterface
{
    public function __construct(UserPurchaseCode $model)
    {
        $this->model = $model;
    }

    public function getByPhone(string $phone): Collection
    {
        return $this->model->where('phone', $phone)->get();
    }

    public function getByCode(string $purchaseCode): ?UserPurchaseCode
    {
        return $this->model->where('purchase_code', $purchaseCode)->first();
    }

    public function getActive(): Collection
    {
        return $this->model->where('status', 'ACTIVE')->get();
    }
}
