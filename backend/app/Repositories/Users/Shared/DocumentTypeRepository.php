<?php

namespace App\Repositories\Users\Shared;

use App\Models\Users\Shared\DocumentType;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Shared\Contracts\DocumentTypeRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

class DocumentTypeRepository extends BaseRepository implements DocumentTypeRepositoryContract
{
    public function __construct(DocumentType $model)
    {
        parent::__construct($model);
    }

    public function getByCountry($countryId): Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    public function getActiveByCountry($countryId): Collection
    {
        return $this->model->where('country_id', $countryId)
            ->where('is_active', true)
            ->get();
    }

    public function getByCode(string $code): ?DocumentType
    {
        return $this->model->where('code', $code)->first();
    }

    public function getByCountryAndCode($countryId, string $code): ?DocumentType
    {
        return $this->model->where('country_id', $countryId)
            ->where('code', $code)
            ->first();
    }

    public function getActiveDocumentTypes(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    public function getInactiveDocumentTypes(): Collection
    {
        return $this->model->where('is_active', false)->get();
    }

    public function searchByDescription(string $searchTerm): Collection
    {
        return $this->model->where('description', 'LIKE', "%{$searchTerm}%")->get();
    }

    public function activateDocumentType($documentTypeId): bool
    {
        return $this->model->where('id', $documentTypeId)
            ->update(['is_active' => true]) > 0;
    }

    public function deactivateDocumentType($documentTypeId): bool
    {
        return $this->model->where('id', $documentTypeId)
            ->update(['is_active' => false]) > 0;
    }

    public function existsByCode(string $code, $countryId = null): bool
    {
        $query = $this->model->where('code', $code);

        if ($countryId !== null) {
            $query->where('country_id', $countryId);
        }

        return $query->exists();
    }

    public function createDocumentType($countryId, string $code, string $description, bool $isActive = true): DocumentType
    {
        return $this->model->create([
            'country_id' => $countryId,
            'code' => $code,
            'description' => $description,
            'is_active' => $isActive
        ]);
    }

    public function updateDocumentType($documentTypeId, array $data): bool
    {
        return $this->model->where('id', $documentTypeId)
            ->update($data) > 0;
    }

    public function getDocumentTypesByStatus(bool $isActive = true): Collection
    {
        return $this->model->where('is_active', $isActive)->get();
    }

    public function getGlobalDocumentTypes(): Collection
    {
        return $this->model->whereNull('country_id')->get();
    }

    public function getCountrySpecificDocumentTypes($countryId): Collection
    {
        return $this->model->whereNotNull('country_id')
            ->where('country_id', $countryId)
            ->get();
    }
}