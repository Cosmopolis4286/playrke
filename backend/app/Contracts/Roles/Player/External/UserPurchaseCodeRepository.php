<?php

namespace App\Repositories\Roles\Player\External;

use App\Models\Users\Player\External\UserPurchaseCode;
use App\Repositories\BaseRepository;
use App\Repositories\Roles\Player\External\Contracts\UserPurchaseCodeRepositoryContract;

/**
 * Repositorio para UserPurchaseCode.
 */
class UserPurchaseCodeRepository extends BaseRepository implements UserPurchaseCodeRepositoryContract
{
    public function __construct(UserPurchaseCode $model)
    {
        $this->model = $model;
    }

    public function getByUser(string $userId)
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function getByCode(string $code)
    {
        return $this->model->where('code', $code)->first();
    }

    public function getActive()
    {
        return $this->model->where('status', 'active')->get();
    }
}
