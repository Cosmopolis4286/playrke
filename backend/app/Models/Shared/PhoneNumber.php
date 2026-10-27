<?php

namespace App\Models\Shared;

use App\Casts\AsActiveStatusEnum;
use App\Models\Country;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para la gestión de números de teléfono de usuarios.
 *
 * Representa los números de teléfono asociados a usuarios con información
 * del país y estado de activación.
 */
class PhoneNumber extends Model
{
    use SnowflakeId;

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => AsActiveStatusEnum::class,
    ];

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',       // ID del usuario propietario
        'country_id',    // ID del país del número
        'phone_number',  // Número de teléfono
        'status',     // Estado del número (activo/inactivo)
    ];

    /**
     * Verificar si el número pertenece a un usuario específico.
     *
     * @param string $userId ID del usuario
     */
    public function belongsToUser(string $userId) : bool
    {
        return $this->user_id === $userId;
    }

    /**
     * Relación con el país del número.
     */
    public function country() : BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Obtener solo los dígitos del número de teléfono.
     */
    public function getCleanNumber() : string
    {
        return preg_replace('/[^\d]/', '', $this->phone_number);
    }

    /**
     * Obtener el código de país del número si está presente.
     */
    public function getCountryCodeFromNumber() : ?string
    {
        if (strpos($this->phone_number, '+') === 0) {
            // Extraer posible código de país (1-4 dígitos después del +)
            preg_match('/^\+(\d{1,4})/', $this->phone_number, $matches);

            return $matches[1] ?? null;
        }

        return null;
    }

    /**
     * Obtener el número formateado con información del país.
     */
    public function getFormattedNumber() : string
    {
        $countryName = $this->country->name ?? 'País desconocido';
        $countryCode = $this->country->calling_code ?? '';

        return sprintf(
            '%s (%s%s)',
            $this->phone_number,
            $countryCode ? '+' . $countryCode : '',
            $countryName ? ' - ' . $countryName : '',
        );
    }

    /**
     * Verificar si el número incluye código de país.
     */
    public function hasCountryCode() : bool
    {
        return $this->getCountryCodeFromNumber() !== null;
    }

    /**
     * Verificar si el número tiene un formato válido básico.
     */
    public function hasValidFormat() : bool
    {
        // Verificar que contenga solo números, espacios, guiones, paréntesis y signo +
        if (! preg_match('/^[\+\d\s\-\(\)]+$/', $this->phone_number)) {
            return false;
        }

        // Verificar longitud mínima y máxima del número limpio
        $cleanNumber = $this->getCleanNumber();
        $length = strlen($cleanNumber);

        return $length >= 7 && $length <= 15;
    }

    /**
     * Verificar si el número está activo.
     */
    public function isActive() : bool
    {
        return $this->status === true;
    }

    /**
     * Verificar si es un número móvil (formato básico).
     */
    public function isMobileNumber() : bool
    {
        $cleanNumber = $this->getCleanNumber();

        // Verificaciones básicas para números móviles comunes
        // Esto puede expandirse con lógica específica por país
        return strlen($cleanNumber) >= 10;
    }

    /**
     * Scope para obtener números activos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope para filtrar por país.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCountry($query, string $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope para filtrar por número de teléfono.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPhoneNumber($query, string $phoneNumber)
    {
        return $query->where('phone_number', $phoneNumber);
    }

    /**
     * Scope para filtrar por usuario.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para buscar números por patrón parcial.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByPattern($query, string $pattern)
    {
        return $query->where('phone_number', 'LIKE', "%{$pattern}%");
    }

    /**
     * Mutator para limpiar y formatear el número antes de guardarlo.
     */
    public function setPhoneNumberAttribute(string $value) : void
    {
        // Limpiar espacios extra pero mantener formato básico
        $this->attributes['phone_number'] = trim($value);
    }

    /**
     * Relación con el usuario propietario.
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
