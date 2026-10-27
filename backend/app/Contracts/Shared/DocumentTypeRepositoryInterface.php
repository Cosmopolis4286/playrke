<?php

namespace App\Contracts\Shared;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Shared\DocumentType;
use Illuminate\Database\Eloquent\Collection;

interface DocumentTypeRepositoryInterface extends BaseRepositoryInterface
{
    public function activateDocumentType(int $documentTypeId) : bool;

    public function createDocumentType(int $countryId, string $code, string $description, bool $isActive = true) : DocumentType;

    public function deactivateDocumentType(int $documentTypeId) : bool;

    public function existsByCode(string $code, ?int $countryId = null) : bool;

    public function getActiveByCountry(int $countryId) : Collection;

    public function getActiveDocumentTypes() : Collection;

    public function getByCode(string $code) : ?DocumentType;

    public function getByCountry(int $countryId) : Collection;

    public function getByCountryAndCode(int $countryId, string $code) : ?DocumentType;

    public function getCountrySpecificDocumentTypes(int $countryId) : Collection;

    public function getDocumentTypesByStatus(bool $isActive = true) : Collection;

    public function getGlobalDocumentTypes() : Collection;

    public function getInactiveDocumentTypes() : Collection;

    public function searchByDescription(string $searchTerm) : Collection;

    public function updateDocumentType(int $documentTypeId, array $data) : bool;
}
