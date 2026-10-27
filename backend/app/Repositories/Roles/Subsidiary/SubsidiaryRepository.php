<?php

namespace App\Repositories\Roles\Subsidiary;

use App\Contracts\Roles\Subsidiary\SubsidiaryRepositoryInterface;
use App\Models\Roles\Subsidiary;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de agencias/sucursales.
 * Sigue el mismo patrón que CommerceRepository.
 */
class SubsidiaryRepository extends BaseRepository implements SubsidiaryRepositoryInterface
{
    public function __construct(Subsidiary $model)
    {
        $this->model = $model;
    }

    public function activate($id) : bool
    {
        return $this->updateStatus($id, 'ACTIVE');
    }

    public function block($id) : bool
    {
        return $this->updateStatus($id, 'BLOCKED');
    }

    public function deactivate($id) : bool
    {
        return $this->updateStatus($id, 'INACTIVE');
    }

    public function existsValue(string $column, $value) : bool
    {
        return $this->model->where($column, $value)->exists();
    }

    public function getBlockedRecords() : Collection
    {
        return $this->model->where('status', 'BLOCKED')->get();
    }

    public function getByCodeUUID(string $code, array $fields = ['*']) : ?Subsidiary
    {
        // Subsidiary no tiene código único propio (a diferencia de Commerce)
        return null;
    }

    public function getByCommerce($commerceId) : Collection
    {
        return $this->model->where('commerce_id', $commerceId)->get();
    }

    public function getByCountry($countryId) : Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    public function getByUserId($userId, array $fields = ['*']) : ?Subsidiary
    {
        return $this->model->select($fields)
            ->where('user_id', $userId)
            ->first();
    }

    public function getInactiveRecords() : Collection
    {
        return $this->model->where('status', 'INACTIVE')->get();
    }

    public function getValueByCodeUUID(string $code, string $field)
    {
        return null;
    }

    public function updateStatus($id, string $status) : bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => $status]) > 0;
    }
}
