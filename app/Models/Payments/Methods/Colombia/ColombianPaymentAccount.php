<?php

namespace App\Models\Payments\Methods\Colombia;

use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para la gestión de métodos de pago de Colombia.
 *
 * Representa las cuentas de pago colombianas asociadas a usuarios,
 * incluyendo Bancolombia, Daviplata y Nequi.
 */
class ColombianPaymentAccount extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'colombian_payment_accounts';

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'gateway',          // Gateway de pago (BANCOLOMBIA, DAVIPLATA, NEQUI)
        'user_name',       // Nombre del titular de la cuenta
        'account_number',  // Número de cuenta
        'document_number', // Número de documento
        'user_id',         // ID del usuario propietario
        'is_active',       // Estado de la cuenta
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Mapeo de códigos de gateway a nombres legibles.
     *
     * @var array<string, string>
     */
    protected static array $gatewayNames = [
        'BANCOLOMBIA' => 'Bancolombia',
        'DAVIPLATA' => 'Daviplata',
        'NEQUI' => 'Nequi'
    ];

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
     * Accessor para obtener el nombre legible del gateway.
     *
     * @return string
     */
    public function getGatewayNameAttribute(): string
    {
        return self::$gatewayNames[$this->gateway] ?? 'Desconocido';
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
     * Scope para filtrar por gateway.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $gateway
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope para obtener cuentas activas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para filtrar por número de documento.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $documentNumber
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDocument($query, string $documentNumber)
    {
        return $query->where('document_number', $documentNumber);
    }

    /**
     * Scope para filtrar por número de cuenta.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $accountNumber
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAccountNumber($query, string $accountNumber)
    {
        return $query->where('account_number', $accountNumber);
    }

    /**
     * Obtener la información completa de la cuenta formateada.
     *
     * @return string
     */
    public function getFormattedInfo(): string
    {
        return sprintf(
            '%s - %s (%s) - Doc: %s',
            $this->gateway_name,
            $this->user_name,
            $this->account_number,
            $this->document_number
        );
    }

    /**
     * Verificar si la cuenta pertenece a un usuario específico.
     *
     * @param string $userId ID del usuario
     * @return bool
     */
    public function belongsToUser(string $userId): bool
    {
        return $this->user_id === $userId;
    }

    /**
     * Verificar si la cuenta está activa.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Verificar si es una cuenta de Bancolombia.
     *
     * @return bool
     */
    public function isBancolombia(): bool
    {
        return $this->gateway === 'BANCOLOMBIA';
    }

    /**
     * Verificar si es una cuenta de Daviplata.
     *
     * @return bool
     */
    public function isDaviplata(): bool
    {
        return $this->gateway === 'DAVIPLATA';
    }

    /**
     * Verificar si es una cuenta de Nequi.
     *
     * @return bool
     */
    public function isNequi(): bool
    {
        return $this->gateway === 'NEQUI';
    }

    /**
     * Obtener todos los gateways disponibles.
     *
     * @return array<string, string>
     */
    public static function getAvailableGateways(): array
    {
        return self::$gatewayNames;
    }

    /**
     * Validar si un gateway es válido.
     *
     * @param string $gateway Gateway a validar
     * @return bool
     */
    public static function isValidGateway(string $gateway): bool
    {
        return array_key_exists($gateway, self::$gatewayNames);
    }
}
