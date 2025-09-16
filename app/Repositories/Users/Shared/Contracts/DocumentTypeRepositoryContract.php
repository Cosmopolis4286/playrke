<?php

namespace App\Repositories\Users\Shared\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use App\Models\Users\Shared\DocumentType;
use Illuminate\Database\Eloquent\Collection;

interface DocumentTypeRepositoryContract extends BaseRepositoryContract
{
    public function getByCountry($countryId): Collection;

    public function getActiveByCountry($countryId): Collection;

    public function getByCode(string $code): ?DocumentType;

    public function getByCountryAndCode($countryId, string $code): ?DocumentType;

    public function getActiveDocumentTypes(): Collection;

    public function getInactiveDocumentTypes(): Collection;

    public function searchByDescription(string $searchTerm): Collection;

    public function activateDocumentType($documentTypeId): bool;

    public function deactivateDocumentType($documentTypeId): bool;

    public function existsByCode(string $code, $countryId = null): bool;

    public function createDocumentType($countryId, string $code, string $description, bool $isActive = true): DocumentType;

    public function updateDocumentType($documentTypeId, array $data): bool;

    public function getDocumentTypesByStatus(bool $isActive = true): Collection;

    public function getGlobalDocumentTypes(): Collection;

    public function getCountrySpecificDocumentTypes($countryId): Collection;
}