<?php

namespace App\Repositories\Users\Shared;

use App\Models\Users\Shared\DocumentType;
use App\Repositories\BaseRepository;
use App\Repositories\Users\Shared\Contracts\DocumentTypeRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

class DocumentTypeRepository extends BaseRepository implements DocumentTypeRepositoryContract
{
    public function __construct(DocumentType $model)
    {
        parent::__construct($model);
    }

    /** ----------------------------------------
     *  QUERIES BÁSICAS
     * ---------------------------------------- */

    public function getByCountry(int $countryId): Collection
    {
        return $this->model
            ->where('country_id', $countryId)
            ->get();
    }

    public function getActiveByCountry(int $countryId): Collection
    {
        return $this->model
            ->where('country_id', $countryId)
            ->where('status', true)
            ->get();
    }

    public function getByCode(string $code): ?DocumentType
    {
        return $this->model
            ->where('code', $code)
            ->first();
    }

    public function getByCountryAndCode(int $countryId, string $code): ?DocumentType
    {
        return $this->model
            ->where('country_id', $countryId)
            ->where('code', $code)
            ->first();
    }

    /** ----------------------------------------
     *  FILTROS POR ESTADO
     * ---------------------------------------- */

    public function getActiveDocumentTypes(): Collection
    {
        return $this->getDocumentTypesByStatus(true);
    }

    public function getInactiveDocumentTypes(): Collection
    {
        return $this->getDocumentTypesByStatus(false);
    }

    public function getDocumentTypesByStatus(bool $isActive = true): Collection
    {
        return $this->model
            ->where('status', $isActive)
            ->get();
    }

    /** ----------------------------------------
     *  BÚSQUEDAS
     * ---------------------------------------- */

    public function searchByDescription(string $searchTerm): Collection
    {
        return $this->model
            ->where('description', 'LIKE', "%{$searchTerm}%")
            ->get();
    }

    public function existsByCode(string $code, ?int $countryId = null): bool
    {
        $query = $this->model->where('code', $code);

        if ($countryId !== null) {
            $query->where('country_id', $countryId);
        }

        return $query->exists();
    }

    /** ----------------------------------------
     *  CRUD
     * ---------------------------------------- */

    public function createDocumentType(
        int $countryId,
        string $code,
        string $description,
        bool $isActive = true
    ): DocumentType {
        return $this->model->create([
            'country_id'  => $countryId,
            'code'        => $code,
            'description' => $description,
            'status'   => $isActive,
        ]);
    }

    public function updateDocumentType(int $documentTypeId, array $data): bool
    {
        return $this->model
            ->where('id', $documentTypeId)
            ->update($data) > 0;
    }

    public function activateDocumentType(int $documentTypeId): bool
    {
        return $this->updateDocumentType($documentTypeId, ['status' => true]);
    }

    public function deactivateDocumentType(int $documentTypeId): bool
    {
        return $this->updateDocumentType($documentTypeId, ['status' => false]);
    }

    /** ----------------------------------------
     *  ALCANCE GLOBAL VS ESPECÍFICO
     * ---------------------------------------- */

    public function getGlobalDocumentTypes(): Collection
    {
        return $this->model
            ->whereNull('country_id')
            ->get();
    }

    public function getCountrySpecificDocumentTypes(int $countryId): Collection
    {
        return $this->model
            ->where('country_id', $countryId)
            ->get();
    }
}
