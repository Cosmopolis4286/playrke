<?php

namespace App\Repositories\Roles\Company;

use App\Models\Roles\Company;
use App\Repositories\BaseRepository;
use App\Contracts\Roles\Company\CompanyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CompanyRepository extends BaseRepository implements CompanyRepositoryInterface
{
    public function __construct(Company $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug): ?Company
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function findByName(string $name): ?Company
    {
        return $this->model->where('name', $name)->first();
    }

    public function getCompaniesWithUsers(): Collection
    {
        return $this->model->whereHas('users')->get();
    }

    public function getCompaniesByUserCount(int $minUsers = 1): Collection
    {
        return $this->model->withCount('users')
            ->having('users_count', '>=', $minUsers)
            ->get();
    }

    public function createWithSlug(string $name, ?string $slug = null): Company
    {
        if (is_null($slug)) {
            $slug = Str::slug($name);
        }

        return $this->model->create([
            'name' => $name,
            'slug' => $slug
        ]);
    }

    public function updateSlug(int $companyId, string $slug): bool
    {
        return $this->model->where('id', $companyId)
            ->update(['slug' => $slug]) > 0;
    }

    public function searchByName(string $searchTerm): Collection
    {
        return $this->model->where('name', 'LIKE', "%{$searchTerm}%")->get();
    }

    public function getUsersCount(int $companyId): int
    {
        $company = $this->model->withCount('users')->find($companyId);
        return $company ? $company->users_count : 0;
    }

    public function hasUsers(int $companyId): bool
    {
        return $this->model->whereHas('users')
            ->where('id', $companyId)
            ->exists();
    }

    public function getActiveCompanies(): Collection
    {
        return $this->model->whereHas('users')->get();
    }
}
