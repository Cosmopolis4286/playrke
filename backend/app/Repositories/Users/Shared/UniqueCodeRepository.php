<?php

namespace App\Repositories\Users\Shared;

use App\Models\Shared\UniqueCode;
use App\Repositories\BaseRepository;
use App\Repositories\Users\Shared\Contracts\UniqueCodeRepositoryContract;

/**
 * Repositorio para UniqueCode.
 */
class UniqueCodeRepository extends BaseRepository implements UniqueCodeRepositoryContract
{
    public function __construct(UniqueCode $model)
    {
        parent::__construct($model);
    }

    public function getByCode(string $code)
    {
        return $this->model->where('code', $code)->first();
    }

    public function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        } while (!$this->isCodeUnique($code));

        return $code;
    }

    public function isCodeUnique(string $code): bool
    {
        return !$this->model->where('code', $code)->exists();
    }
}
