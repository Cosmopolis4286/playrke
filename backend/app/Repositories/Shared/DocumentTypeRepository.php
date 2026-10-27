<?php

namespace App\Repositories\Shared;

use App\Contracts\Shared\DocumentTypeRepositoryInterface;
use App\Models\Shared\DocumentType;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class DocumentTypeRepository extends BaseRepository implements DocumentTypeRepositoryInterface
{
    public function __construct(DocumentType $model)
    {
        $this->model = $model;
    }

    public function activateDocumentType(int $documentTypeId) : bool
    {
        return $this->updateDocumentType($documentTypeId, ['status' => true]);
    }

    /** ----------------------------------------
     *  CRUD
     * ---------------------------------------- */
    public function createDocumentType(
        int $countryId,
        string $code,
        string $description,
        bool $isActive = true,
    ) : DocumentType {
        return $this->model->create([
            'country_id' => $countryId,
            'code' => $code,
            'description' => $description,
            'status' => $isActive,
        ]);
    }

    public function deactivateDocumentType(int $documentTypeId) : bool
    {
        return $this->updateDocumentType($documentTypeId, ['status' => false]);
    }

    public function existsByCode(string $code, ?int $countryId = null) : bool
    {
        $query = $this->model->where('code', $code);

        if ($countryId !== null) {
            $query->where('country_id', $countryId);
        }

        return $query->exists();
    }

    public function getActiveByCountry(int $countryId) : Collection
    {
        return $this->model
            ->where('country_id', $countryId)
            ->where('status', true)
            ->get();
    }

    /** ----------------------------------------
     *  FILTROS POR ESTADO
     * ---------------------------------------- */
    public function getActiveDocumentTypes() : Collection
    {
        return $this->getDocumentTypesByStatus(true);
    }

    public function getByCode(string $code) : ?DocumentType
    {
        return $this->model
            ->where('code', $code)
            ->first();
    }

    /** ----------------------------------------
     *  QUERIES BÁSICAS
     * ---------------------------------------- */
    public function getByCountry(int $countryId) : Collection
    {
        return $this->model
            ->where('country_id', $countryId)
            ->get();
    }

    public function getByCountryAndCode(int $countryId, string $code) : ?DocumentType
    {
        return $this->model
            ->where('country_id', $countryId)
            ->where('code', $code)
            ->first();
    }

    public function getCountrySpecificDocumentTypes(int $countryId) : Collection
    {
        return $this->model
            ->where('country_id', $countryId)
            ->get();
    }

    public function getDocumentTypesByStatus(bool $isActive = true) : Collection
    {
        return $this->model
            ->where('status', $isActive)
            ->get();
    }

    /** ----------------------------------------
     *  ALCANCE GLOBAL VS ESPECÍFICO
     * ---------------------------------------- */
    public function getGlobalDocumentTypes() : Collection
    {
        return $this->model
            ->whereNull('country_id')
            ->get();
    }

    public function getInactiveDocumentTypes() : Collection
    {
        return $this->getDocumentTypesByStatus(false);
    }

    /** ----------------------------------------
     *  BÚSQUEDAS
     * ---------------------------------------- */
    public function searchByDescription(string $searchTerm) : Collection
    {
        return $this->model
            ->where('description', 'LIKE', "%{$searchTerm}%")
            ->get();
    }

    public function updateDocumentType(int $documentTypeId, array $data) : bool
    {
        return $this->model
            ->where('id', $documentTypeId)
            ->update($data) > 0;
    }
}
