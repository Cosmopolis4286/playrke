<?php

namespace App\Repositories\Users\Roles\Treasurer;

use App\Models\Users\Roles\Treasurer\Treasurer;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Roles\Treasurer\Contracts\TreasurerRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para la gestión de tesoreros.
 */
class TreasurerRepository extends BaseRepository implements TreasurerRepositoryContract
{
    public function __construct(Treasurer $model)
    {
        parent::__construct($model);
    }

    public function getByCountry(string $countryId): Collection
    {
        return $this->model->where('country', $countryId)->get();
    }

    public function getByCurrency(string $currencyId): Collection
    {
        return $this->model->where('currency', $currencyId)->get();
    }

    public function getByUuidCode(string $uuidCode): mixed
    {
        return $this->model->where('uuid_code', $uuidCode)->first();
    }

    public function createTreasurer(array $data): mixed
    {
        $errors = $this->validateTreasurerData($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Datos inválidos: ' . json_encode($errors));
        }
        return $this->model->create($data);
    }

    public function updateStatus(string $treasurerId, bool $status): bool
    {
        return $this->model->where('id', $treasurerId)->update(['status' => $status]) > 0;
    }

    public function validateTreasurerData(array $data, ?string $excludeId = null): array
    {
        $errors = [];
        if (isset($data['name']) && empty(trim($data['name']))) {
            $errors['name'] = 'El nombre es requerido';
        }
        return $errors;
    }
}
