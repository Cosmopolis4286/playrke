<?php

namespace App\Repositories\Directories\Fiat\Venezuela;

use App\Models\Directories\Fiat\Venezuela\PagoMovil;
use App\Repositories\BaseRepository;
use App\Contracts\Directories\Fiat\Venezuela\PagoMovilRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para la gestión de Pago Móvil Venezuela.
 *
 * Implementa las operaciones específicas para el modelo PagoMovil,
 * incluyendo validaciones, consultas por parámetros y estadísticas.
 */
class PagoMovilRepository extends BaseRepository implements PagoMovilRepositoryInterface
{
    /**
     * Constructor del repositorio.
     *
     * @param PagoMovil $model Instancia del modelo PagoMovil
     */
    public function __construct(PagoMovil $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getByUser(string $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByBank(string $bankId): Collection
    {
        return $this->model->where('bank_id', $bankId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByDocumentType(string $documentTypeId): Collection
    {
        return $this->model->where('document_type_id', $documentTypeId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByDocument(string $document): Collection
    {
        return $this->model->where('document', $document)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByPhone(string $phone): Collection
    {
        return $this->model->where('phone', $phone)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findByAllParameters(
        string $documentTypeId,
        string $document,
        string $bankId,
        string $phone
    ): ?PagoMovil {
        return $this->model->where([
            'document_type_id' => $documentTypeId,
            'document_number' => $document,
            'bank_id' => $bankId,
            'phone' => $phone,
        ])->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getUserByPagoMovil(
        string $documentTypeId,
        string $document,
        string $bankId,
        string $phone
    ): ?string {
        return $this->model->where([
            'document_type_id' => $documentTypeId,
            'document_number' => $document,
            'bank_id' => $bankId,
            'phone' => $phone,
        ])->value('user_id');
    }

    /**
     * {@inheritDoc}
     */
    public function existsPagoMovil(
        string $documentTypeId,
        string $document,
        string $bankId,
        string $phone,
        ?string $excludeUserId = null
    ): bool {
        $query = $this->model->where([
            'document_type_id' => $documentTypeId,
            'document_number' => $document,
            'bank_id' => $bankId,
            'phone' => $phone,
        ]);

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function createPagoMovil(
        string $documentTypeId,
        string $document,
        string $phone,
        string $bankId,
        string $userId
    ): PagoMovil {
        return $this->model->create([
            'document_type_id' => $documentTypeId,
            'document_number' => $document,
            'phone' => $phone,
            'bank_id' => $bankId,
            'user_id' => $userId
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updatePhone(string $pagoMovilId, string $phone): bool
    {
        return $this->model->where('id', $pagoMovilId)
            ->update(['phone' => $phone]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateDocument(
        string $pagoMovilId,
        string $documentTypeId,
        string $document
    ): bool {
        return $this->model->where('id', $pagoMovilId)
            ->update([
                'document_type_id' => $documentTypeId,
                'document' => $document
            ]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateBank(string $pagoMovilId, string $bankId): bool
    {
        return $this->model->where('id', $pagoMovilId)
            ->update(['bank_id' => $bankId]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function deletePagoMovil(string $pagoMovilId): bool
    {
        return $this->model->where('id', $pagoMovilId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteByUser(string $userId): int
    {
        return $this->model->where('user_id', $userId)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getPagoMovilStatistics(): array
    {
        $total = $this->model->count();
        $uniqueUsers = $this->model->distinct('user_id')->count();
        $uniqueBanks = $this->model->distinct('bank_id')->count();
        $uniquePhones = $this->model->distinct('phone')->count();
        $uniqueDocuments = $this->model->distinct('document')->count();

        return [
            'total_registrations' => $total,
            'unique_users' => $uniqueUsers,
            'unique_banks' => $uniqueBanks,
            'unique_phones' => $uniquePhones,
            'unique_documents' => $uniqueDocuments,
            'average_registrations_per_user' => $uniqueUsers > 0 ? round($total / $uniqueUsers, 2) : 0,
            'phone_reuse_percentage' => $total > 0 ? round((($total - $uniquePhones) / $total) * 100, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getBankStatistics(string $bankId): array
    {
        $total = $this->model->where('bank_id', $bankId)->count();
        $uniqueUsers = $this->model->where('bank_id', $bankId)->distinct('user_id')->count();
        $uniquePhones = $this->model->where('bank_id', $bankId)->distinct('phone')->count();

        return [
            'total_registrations' => $total,
            'unique_users' => $uniqueUsers,
            'unique_phones' => $uniquePhones,
            'average_registrations_per_user' => $uniqueUsers > 0 ? round($total / $uniqueUsers, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getUserStatistics(string $userId): array
    {
        $total = $this->model->where('user_id', $userId)->count();
        $uniqueBanks = $this->model->where('user_id', $userId)->distinct('bank_id')->count();
        $uniquePhones = $this->model->where('user_id', $userId)->distinct('phone')->count();

        return [
            'total_registrations' => $total,
            'unique_banks' => $uniqueBanks,
            'unique_phones' => $uniquePhones,
            'has_multiple_banks' => $uniqueBanks > 1,
            'has_multiple_phones' => $uniquePhones > 1
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentRegistrations(int $days = 30): Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByPartialDocument(string $partialDocument): Collection
    {
        return $this->model->where('document', 'LIKE', "%{$partialDocument}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByPartialPhone(string $partialPhone): Collection
    {
        return $this->model->where('phone', 'LIKE', "%{$partialPhone}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getUniqueBanks(): Collection
    {
        return $this->model->distinct()->pluck('bank_id');
    }

    /**
     * {@inheritDoc}
     */
    public function getUniqueDocumentTypes(): Collection
    {
        return $this->model->distinct()->pluck('document_type_id');
    }

    /**
     * {@inheritDoc}
     */
    public function isPhoneUsedByOtherUser(string $phone, ?string $excludeUserId = null): bool
    {
        $query = $this->model->where('phone', $phone);

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getUsersSharingPhone(string $phone): Collection
    {
        return $this->model->where('phone', $phone)
            ->distinct('user_id')
            ->pluck('user_id');
    }
}
