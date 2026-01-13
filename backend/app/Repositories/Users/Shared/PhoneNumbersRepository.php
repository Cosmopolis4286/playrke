<?php

namespace App\Repositories\Users\Shared;

use App\Models\Users\Shared\PhoneNumbers;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Shared\Contracts\PhoneNumbersRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para la gestión de números de teléfono.
 *
 * Implementa las operaciones específicas para el modelo PhoneNumbers,
 * incluyendo validaciones por país, usuario y formato de números.
 */
class PhoneNumbersRepository extends BaseRepository implements PhoneNumbersRepositoryContract
{
    /**
     * Constructor del repositorio.
     *
     * @param PhoneNumbers $model Instancia del modelo PhoneNumbers
     */
    public function __construct(PhoneNumbers $model)
    {
        parent::__construct($model);
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
    public function getActiveByUser(string $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('status', true)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCountry(string $countryId): Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveByCountry(string $countryId): Collection
    {
        return $this->model->where('country_id', $countryId)
            ->where('status', true)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByPhoneNumber(string $phoneNumber): ?PhoneNumbers
    {
        return $this->model->where('phone_number', $phoneNumber)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function existsPhoneNumber(string $phoneNumber): bool
    {
        return $this->model->where('phone_number', $phoneNumber)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function isPhoneNumberActive(string $phoneNumber): bool
    {
        return $this->model->where('phone_number', $phoneNumber)
            ->where('status', true)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveNumbers(): Collection
    {
        return $this->getByStatus(true);
    }

    /**
     * {@inheritDoc}
     */
    public function getInactiveNumbers(): Collection
    {
        return $this->getByStatus(false);
    }

    /**
     * {@inheritDoc}
     */
    public function getByUserAndCountry(string $userId, string $countryId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('country_id', $countryId)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createPhoneNumber(
        string $userId,
        string $countryId,
        string $phoneNumber,
        bool $isActive = true
    ): PhoneNumbers {
        return $this->model->create([
            'user_id' => $userId,
            'country_id' => $countryId,
            'phone_number' => $phoneNumber,
            'status' => $isActive
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(string $phoneNumberId, bool $isActive): bool
    {
        return $this->model->where('id', $phoneNumberId)
            ->update(['status' => $isActive]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function activatePhoneNumber(string $phoneNumberId): bool
    {
        return $this->updateStatus($phoneNumberId, true);
    }

    /**
     * {@inheritDoc}
     */
    public function deactivatePhoneNumber(string $phoneNumberId): bool
    {
        return $this->updateStatus($phoneNumberId, false);
    }

    /**
     * {@inheritDoc}
     */
    public function updatePhoneNumber(string $phoneNumberId, string $newPhoneNumber): bool
    {
        return $this->model->where('id', $phoneNumberId)
            ->update(['phone_number' => $newPhoneNumber]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateCountry(string $phoneNumberId, string $countryId): bool
    {
        return $this->model->where('id', $phoneNumberId)
            ->update(['country_id' => $countryId]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function deletePhoneNumber(string $phoneNumberId): bool
    {
        return $this->model->where('id', $phoneNumberId)->delete() > 0;
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
    public function bulkUpdateStatus(array $phoneNumberIds, bool $isActive): int
    {
        return $this->model->whereIn('id', $phoneNumberIds)
            ->update(['status' => $isActive]);
    }

    /**
     * {@inheritDoc}
     */
    public function getPhoneNumberStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', true)->count();
        $inactive = $this->model->where('status', false)->count();
        $uniqueUsers = $this->model->distinct('user_id')->count();
        $uniqueCountries = $this->model->distinct('country_id')->count();
        $duplicateNumbers = $this->model->select('phone_number')
            ->groupBy('phone_number')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        return [
            'total_numbers' => $total,
            'active_numbers' => $active,
            'inactive_numbers' => $inactive,
            'unique_users' => $uniqueUsers,
            'unique_countries' => $uniqueCountries,
            'duplicate_numbers' => $duplicateNumbers,
            'numbers_per_user' => $uniqueUsers > 0 ? round($total / $uniqueUsers, 2) : 0,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCountryStatistics(string $countryId): array
    {
        $total = $this->model->where('country_id', $countryId)->count();
        $active = $this->model->where('country_id', $countryId)
            ->where('status', true)->count();
        $uniqueUsers = $this->model->where('country_id', $countryId)
            ->distinct('user_id')->count();

        return [
            'total_numbers' => $total,
            'active_numbers' => $active,
            'inactive_numbers' => $total - $active,
            'unique_users' => $uniqueUsers,
            'numbers_per_user' => $uniqueUsers > 0 ? round($total / $uniqueUsers, 2) : 0,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getUserStatistics(string $userId): array
    {
        $total = $this->model->where('user_id', $userId)->count();
        $active = $this->model->where('user_id', $userId)
            ->where('status', true)->count();
        $countries = $this->model->where('user_id', $userId)
            ->distinct('country_id')->pluck('country_id');

        return [
            'total_numbers' => $total,
            'active_numbers' => $active,
            'inactive_numbers' => $total - $active,
            'unique_countries' => $countries->count(),
            'countries_used' => $countries->toArray(),
            'has_multiple_countries' => $countries->count() > 1
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentPhoneNumbers(int $days = 30): Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByPartialNumber(string $partialNumber): Collection
    {
        return $this->model->where('phone_number', 'LIKE', "%{$partialNumber}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getUniqueCountries(): Collection
    {
        return $this->model->distinct()->pluck('country_id');
    }

    /**
     * {@inheritDoc}
     */
    public function hasActiveNumberForCountry(string $userId, string $countryId): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('country_id', $countryId)
            ->where('status', true)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryNumberForCountry(string $userId, string $countryId): ?PhoneNumbers
    {
        return $this->model->where('user_id', $userId)
            ->where('country_id', $countryId)
            ->where('status', true)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function isNumberUsedByOtherUser(string $phoneNumber, ?string $excludeUserId = null): bool
    {
        $query = $this->model->where('phone_number', $phoneNumber);

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getUsersSharingNumber(string $phoneNumber): Collection
    {
        return $this->model->where('phone_number', $phoneNumber)
            ->distinct('user_id')
            ->pluck('user_id');
    }

    /**
     * {@inheritDoc}
     */
    public function validatePhoneNumberFormat(string $phoneNumber, string $countryId): bool
    {
        // Validación básica: debe contener solo números, espacios, guiones y signos +
        if (!preg_match('/^[\+\d\s\-\(\)]+$/', $phoneNumber)) {
            return false;
        }

        // Longitud mínima y máxima
        $cleanNumber = preg_replace('/[^\d]/', '', $phoneNumber);
        $length = strlen($cleanNumber);

        if ($length < 7 || $length > 15) {
            return false;
        }

        // Validaciones específicas por país se pueden agregar aquí
        // Por ahora, retornamos true si pasa las validaciones básicas
        return true;
    }
}
