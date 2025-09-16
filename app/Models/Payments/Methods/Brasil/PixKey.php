<?php

namespace App\Models\Payments\Methods\Brasil;

use App\Models\Banks\BankBrazil;
use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para la gestión de claves PIX de Brasil.
 *
 * Representa las claves PIX asociadas a usuarios con información
 * del banco, tipo de clave y valor de la clave PIX.
 */
class PixKey extends Model
{
    use SnowflakeId;

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',           // Nombre descriptivo de la clave PIX
        'bank_id',        // ID del banco brasileño
        'pix_key_type',   // Tipo de clave PIX (CPF, CNPJ, EMAIL, PHONE, RANDOM)
        'pix_key_value',  // Valor de la clave PIX
        'user_id',        // ID del usuario propietario
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'string',
        'bank_id' => 'string',
    ];

    /**
     * Atributos agregados automáticamente a la serialización.
     *
     * @var array<int, string>
     */
    protected $appends = ['chave_pix', 'formatted_key'];

    /**
     * Tipos de clave PIX válidos.
     *
     * @var array<string>
     */
    public static array $pixKeyTypes = ['CPF', 'CNPJ', 'EMAIL', 'PHONE', 'RANDOM'];

    /**
     * Relación con el banco brasileño.
     *
     * @return BelongsTo
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(BankBrazil::class, 'bank_id');
    }

    /**
     * Relación con el usuario propietario.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Accessor para obtener la clave PIX (compatibilidad).
     *
     * @return string
     */
    public function getChavePixAttribute(): string
    {
        return $this->pix_key_value;
    }

    /**
     * Accessor para obtener la clave PIX formateada según el tipo.
     *
     * @return string
     */
    public function getFormattedKeyAttribute(): string
    {
        switch ($this->pix_key_type) {
            case 'CPF':
                return $this->formatCpf($this->pix_key_value);
            case 'CNPJ':
                return $this->formatCnpj($this->pix_key_value);
            case 'PHONE':
                return $this->formatPhone($this->pix_key_value);
            default:
                return $this->pix_key_value;
        }
    }

    /**
     * Scope para filtrar por usuario.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar por banco.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $bankId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByBank($query, string $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    /**
     * Scope para filtrar por tipo de clave PIX.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $pixKeyType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $pixKeyType)
    {
        return $query->where('pix_key_type', $pixKeyType);
    }

    /**
     * Scope para filtrar por valor de clave PIX.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $pixKeyValue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByKeyValue($query, string $pixKeyValue)
    {
        return $query->where('pix_key_value', $pixKeyValue);
    }

    /**
     * Scope para buscar por valor parcial de clave PIX.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByValue($query, string $searchTerm)
    {
        return $query->where('pix_key_value', 'LIKE', "%{$searchTerm}%");
    }

    /**
     * Verificar si la clave PIX pertenece a un usuario específico.
     *
     * @param string $userId ID del usuario
     * @return bool
     */
    public function belongsToUser(string $userId): bool
    {
        return $this->user_id === $userId;
    }

    /**
     * Verificar si es una clave PIX de tipo CPF.
     *
     * @return bool
     */
    public function isCpf(): bool
    {
        return $this->pix_key_type === 'CPF';
    }

    /**
     * Verificar si es una clave PIX de tipo CNPJ.
     *
     * @return bool
     */
    public function isCnpj(): bool
    {
        return $this->pix_key_type === 'CNPJ';
    }

    /**
     * Verificar si es una clave PIX de tipo EMAIL.
     *
     * @return bool
     */
    public function isEmail(): bool
    {
        return $this->pix_key_type === 'EMAIL';
    }

    /**
     * Verificar si es una clave PIX de tipo PHONE.
     *
     * @return bool
     */
    public function isPhone(): bool
    {
        return $this->pix_key_type === 'PHONE';
    }

    /**
     * Verificar si es una clave PIX de tipo RANDOM.
     *
     * @return bool
     */
    public function isRandom(): bool
    {
        return $this->pix_key_type === 'RANDOM';
    }

    /**
     * Obtener información completa de la clave PIX formateada.
     *
     * @return string
     */
    public function getFullInfo(): string
    {
        $bankName = $this->bank->name ?? 'Banco desconocido';
        $typeName = $this->getTypeDisplayName();

        return sprintf(
            '%s - %s (%s) - %s',
            $bankName,
            $typeName,
            $this->formatted_key,
            $this->name ?? 'Sin nombre'
        );
    }

    /**
     * Obtener el nombre legible del tipo de clave PIX.
     *
     * @return string
     */
    public function getTypeDisplayName(): string
    {
        $typeNames = [
            'CPF' => 'CPF',
            'CNPJ' => 'CNPJ',
            'EMAIL' => 'E-mail',
            'PHONE' => 'Teléfono',
            'RANDOM' => 'Clave Aleatoria'
        ];

        return $typeNames[$this->pix_key_type] ?? 'Desconocido';
    }

    /**
     * Formatear CPF para visualización.
     *
     * @param string $cpf CPF sin formato
     * @return string CPF formateado
     */
    private function formatCpf(string $cpf): string
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
        }
        return $cpf;
    }

    /**
     * Formatear CNPJ para visualización.
     *
     * @param string $cnpj CNPJ sin formato
     * @return string CNPJ formateado
     */
    private function formatCnpj(string $cnpj): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
        }
        return $cnpj;
    }

    /**
     * Formatear teléfono para visualización.
     *
     * @param string $phone Teléfono sin formato
     * @return string Teléfono formateado
     */
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (preg_match('/^\+?55(\d{2})(\d{4,5})(\d{4})$/', $phone, $matches)) {
            return sprintf('+55 (%s) %s-%s', $matches[1], $matches[2], $matches[3]);
        }
        return $phone;
    }

    /**
     * Mutator para limpiar el valor de la clave PIX antes de guardarlo.
     *
     * @param string $value
     * @return void
     */
    public function setPixKeyValueAttribute(string $value): void
    {
        // Limpiar espacios y caracteres especiales según el tipo
        $cleanValue = trim($value);

        if ($this->pix_key_type === 'CPF' || $this->pix_key_type === 'CNPJ') {
            $cleanValue = preg_replace('/[^0-9]/', '', $cleanValue);
        } elseif ($this->pix_key_type === 'EMAIL') {
            $cleanValue = strtolower($cleanValue);
        } elseif ($this->pix_key_type === 'PHONE') {
            $cleanValue = preg_replace('/[^0-9+]/', '', $cleanValue);
        }

        $this->attributes['pix_key_value'] = $cleanValue;
    }

    /**
     * Obtener todos los tipos de clave PIX válidos.
     *
     * @return array<string>
     */
    public static function getValidPixKeyTypes(): array
    {
        return self::$pixKeyTypes;
    }

    /**
     * Verificar si un tipo de clave PIX es válido.
     *
     * @param string $pixKeyType Tipo de clave PIX
     * @return bool
     */
    public static function isValidPixKeyType(string $pixKeyType): bool
    {
        return in_array($pixKeyType, self::$pixKeyTypes);
    }
}
