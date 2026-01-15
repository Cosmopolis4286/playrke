<?php

namespace App\Repositories\Shared\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Collection;

interface DocumentTypeRepositoryContract extends BaseRepositoryInterface
{
    public function getByCountry(int $countryId): Collection;

    public function getActiveByCountry(int $countryId): Collection;

    public function getByCode(string $code): ?DocumentType;

    public function getByCountryAndCode(int $countryId, string $code): ?DocumentType;

    public function getActiveDocumentTypes(): Collection;

    public function getInactiveDocumentTypes(): Collection;

    public function searchByDescription(string $searchTerm): Collection;

    public function activateDocumentType(int $documentTypeId): bool;

    public function deactivateDocumentType(int $documentTypeId): bool;

    public function existsByCode(string $code, ?int $countryId = null): bool;

    public function createDocumentType(int $countryId, string $code, string $description, bool $isActive = true): DocumentType;

    public function updateDocumentType(int $documentTypeId, array $data): bool;

    public function getDocumentTypesByStatus(bool $isActive = true): Collection;

    public function getGlobalDocumentTypes(): Collection;

    public function getCountrySpecificDocumentTypes(int $countryId): Collection;
}
