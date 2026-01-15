<?php

namespace App\Repositories\Directories\Fiat\Brazil;

use App\Models\Directories\Fiat\Brazil\PixKey;
use App\Repositories\BaseRepository;
use App\Repositories\Directories\Fiat\Brazil\Contracts\PixKeyRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Repositorio para la gestión de claves PIX de Brasil.
 *
 * Implementa las operaciones específicas para el modelo PixKey,
 * incluyendo validaciones por tipo, gestión de claves y estadísticas.
 */
class PixKeyRepository extends BaseRepository implements PixKeyRepositoryContract
{
    /**
     * Constructor del repositorio.
     *
     * @param PixKey $model Instancia del modelo PixKey
     */
    public function __construct(PixKey $model)
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
    public function getByBank(string $bankId): Collection
    {
        return $this->model->where('bank_id', $bankId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByType(string $pixKeyType): Collection
    {
        return $this->model->where('pix_key_type', $pixKeyType)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByPixKeyValue(string $pixKeyValue): ?PixKey
    {
        return $this->model->where('pix_key_value', $pixKeyValue)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getByTypeAndValue(string $pixKeyType, string $pixKeyValue): Collection
    {
        return $this->model->where('pix_key_type', $pixKeyType)
            ->where('pix_key_value', $pixKeyValue)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function existsPixKey(string $pixKeyValue): bool
    {
        return $this->model->where('pix_key_value', $pixKeyValue)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function userHasPixKey(string $userId, string $pixKeyType, string $pixKeyValue): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('pix_key_type', $pixKeyType)
            ->where('pix_key_value', $pixKeyValue)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getByUserAndType(string $userId, string $pixKeyType): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('pix_key_type', $pixKeyType)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByUserAndBank(string $userId, string $bankId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('bank_id', $bankId)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createPixKey(
        string $userId,
        string $bankId,
        string $pixKeyType,
        string $pixKeyValue,
        ?string $name = null
    ): PixKey {
        return $this->model->create([
            'user_id' => $userId,
            'bank_id' => $bankId,
            'pix_key_type' => $pixKeyType,
            'pix_key_value' => $pixKeyValue,
            'name' => $name
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateName(string $pixKeyId, string $name): bool
    {
        return $this->model->where('id', $pixKeyId)
            ->update(['name' => $name]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateBank(string $pixKeyId, string $bankId): bool
    {
        return $this->model->where('id', $pixKeyId)
            ->update(['bank_id' => $bankId]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updatePixKeyValue(string $pixKeyId, string $pixKeyValue): bool
    {
        return $this->model->where('id', $pixKeyId)
            ->update(['pix_key_value' => $pixKeyValue]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function deletePixKey(string $pixKeyId): bool
    {
        return $this->model->where('id', $pixKeyId)->delete() > 0;
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
    public function deleteByBank(string $bankId): int
    {
        return $this->model->where('bank_id', $bankId)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getPixKeyStatistics(): array
    {
        $total = $this->model->count();
        $uniqueUsers = $this->model->distinct('user_id')->count();
        $uniqueBanks = $this->model->distinct('bank_id')->count();
        $cpfCount = $this->model->where('pix_key_type', 'CPF')->count();
        $cnpjCount = $this->model->where('pix_key_type', 'CNPJ')->count();
        $emailCount = $this->model->where('pix_key_type', 'EMAIL')->count();
        $phoneCount = $this->model->where('pix_key_type', 'PHONE')->count();
        $randomCount = $this->model->where('pix_key_type', 'RANDOM')->count();

        return [
            'total_keys' => $total,
            'unique_users' => $uniqueUsers,
            'unique_banks' => $uniqueBanks,
            'keys_per_user' => $uniqueUsers > 0 ? round($total / $uniqueUsers, 2) : 0,
            'type_distribution' => [
                'CPF' => $cpfCount,
                'CNPJ' => $cnpjCount,
                'EMAIL' => $emailCount,
                'PHONE' => $phoneCount,
                'RANDOM' => $randomCount
            ],
            'type_percentages' => [
                'CPF' => $total > 0 ? round(($cpfCount / $total) * 100, 2) : 0,
                'CNPJ' => $total > 0 ? round(($cnpjCount / $total) * 100, 2) : 0,
                'EMAIL' => $total > 0 ? round(($emailCount / $total) * 100, 2) : 0,
                'PHONE' => $total > 0 ? round(($phoneCount / $total) * 100, 2) : 0,
                'RANDOM' => $total > 0 ? round(($randomCount / $total) * 100, 2) : 0
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeStatistics(string $pixKeyType): array
    {
        $total = $this->model->where('pix_key_type', $pixKeyType)->count();
        $uniqueUsers = $this->model->where('pix_key_type', $pixKeyType)
            ->distinct('user_id')->count();
        $uniqueBanks = $this->model->where('pix_key_type', $pixKeyType)
            ->distinct('bank_id')->count();

        return [
            'total_keys' => $total,
            'unique_users' => $uniqueUsers,
            'unique_banks' => $uniqueBanks,
            'keys_per_user' => $uniqueUsers > 0 ? round($total / $uniqueUsers, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getBankStatistics(string $bankId): array
    {
        $total = $this->model->where('bank_id', $bankId)->count();
        $uniqueUsers = $this->model->where('bank_id', $bankId)
            ->distinct('user_id')->count();
        $types = $this->model->where('bank_id', $bankId)
            ->distinct('pix_key_type')->pluck('pix_key_type');

        return [
            'total_keys' => $total,
            'unique_users' => $uniqueUsers,
            'unique_types' => $types->count(),
            'types_used' => $types->toArray(),
            'keys_per_user' => $uniqueUsers > 0 ? round($total / $uniqueUsers, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getUserStatistics(string $userId): array
    {
        $total = $this->model->where('user_id', $userId)->count();
        $banks = $this->model->where('user_id', $userId)
            ->distinct('bank_id')->pluck('bank_id');
        $types = $this->model->where('user_id', $userId)
            ->distinct('pix_key_type')->pluck('pix_key_type');

        return [
            'total_keys' => $total,
            'unique_banks' => $banks->count(),
            'unique_types' => $types->count(),
            'banks_used' => $banks->toArray(),
            'types_used' => $types->toArray(),
            'has_cpf' => $types->contains('CPF'),
            'has_cnpj' => $types->contains('CNPJ'),
            'has_email' => $types->contains('EMAIL'),
            'has_phone' => $types->contains('PHONE'),
            'has_random' => $types->contains('RANDOM')
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentPixKey(int $days = 30): Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByPartialValue(string $searchTerm): Collection
    {
        return $this->model->where('pix_key_value', 'LIKE', "%{$searchTerm}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByPartialName(string $searchTerm): Collection
    {
        return $this->model->where('name', 'LIKE', "%{$searchTerm}%")->get();
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
    public function getUniqueTypes(): Collection
    {
        return $this->model->distinct()->pluck('pix_key_type');
    }

    /**
     * {@inheritDoc}
     */
    public function hasKeysForBank(string $userId, string $bankId): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('bank_id', $bankId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryKeyForBank(string $userId, string $bankId): ?PixKey
    {
        return $this->model->where('user_id', $userId)
            ->where('bank_id', $bankId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function isKeyUsedByOtherUser(string $pixKeyValue, ?string $excludeUserId = null): bool
    {
        $query = $this->model->where('pix_key_value', $pixKeyValue);

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getUsersSharingKey(string $pixKeyValue): Collection
    {
        return $this->model->where('pix_key_value', $pixKeyValue)
            ->distinct('user_id')
            ->pluck('user_id');
    }

    /**
     * {@inheritDoc}
     */
    public function validatePixKeyFormat(string $pixKeyType, string $pixKeyValue): bool
    {
        switch ($pixKeyType) {
            case 'CPF':
                return $this->isValidCpf($pixKeyValue);
            case 'CNPJ':
                return $this->isValidCnpj($pixKeyValue);
            case 'EMAIL':
                return filter_var($pixKeyValue, FILTER_VALIDATE_EMAIL) !== false;
            case 'PHONE':
                return preg_match('/^\+?55\d{10,11}$/', preg_replace('/[^\d+]/', '', $pixKeyValue));
            case 'RANDOM':
                return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $pixKeyValue);
            default:
                return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function generateRandomPixKey(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * {@inheritDoc}
     */
    public function isValidCpf(string $cpf): bool
    {
        // Limpiar CPF
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Verificar longitud
        if (strlen($cpf) !== 11) {
            return false;
        }

        // Verificar sequências inválidas
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Calcular dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isValidCnpj(string $cnpj): bool
    {
        // Limpiar CNPJ
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Verificar longitud
        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Verificar sequências inválidas
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        // Calcular primer dígito verificador
        $sum = 0;
        $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weights[$i];
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        if ($cnpj[12] != $digit1) {
            return false;
        }

        // Calcular segundo dígito verificador
        $sum = 0;
        $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $weights[$i];
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return $cnpj[13] == $digit2;
    }
}
