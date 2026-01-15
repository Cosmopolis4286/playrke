<?php

namespace App\Repositories\Users\Roles\Company\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Roles\Company;
use Illuminate\Database\Eloquent\Collection;

interface CompanyRepositoryContract extends BaseRepositoryInterface
{
    public function findBySlug(string $slug): ?Company;

    public function findByName(string $name): ?Company;

    public function getCompaniesWithUsers(): Collection;

    public function getCompaniesByUserCount(int $minUsers = 1): Collection;

    public function createWithSlug(string $name, ?string $slug = null): Company;

    public function updateSlug(int $companyId, string $slug): bool;

    public function searchByName(string $searchTerm): Collection;

    public function getUsersCount(int $companyId): int;

    public function hasUsers(int $companyId): bool;

    public function getActiveCompanies(): Collection;
}
