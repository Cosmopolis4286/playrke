<?php

namespace App\Models\Directories\Fiat\Colombia;

use App\Casts\AsActiveStatusEnum;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para la gestión de métodos de pago de Colombia.
 *
 * Representa las cuentas de pago colombianas asociadas a usuarios,
 * incluyendo Bancolombia, Daviplata y Nequi.
 */
class PaymentAccount extends Model
{
    use SnowflakeId;

    /**
     * Mapeo de códigos de gateway a nombres legibles.
     *
     * @var array<string, string>
     */
    protected static array $gatewayNames = [
        'BANCOLOMBIA' => 'Bancolombia',
        'DAVIPLATA' => 'Daviplata',
        'NEQUI' => 'Nequi',
    ];

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
        'gateway',          // Gateway de pago (BANCOLOMBIA, DAVIPLATA, NEQUI)
        'user_name',       // Nombre del titular de la cuenta
        'account_number',  // Número de cuenta
        'document_number', // Número de documento
        'user_id',         // ID del usuario propietario
        'status',       // Estado de la cuenta
    ];

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'payment_accounts_co';

    /**
     * Obtener todos los gateways disponibles.
     *
     * @return array<string, string>
     */
    public static function getAvailableGateways() : array
    {
        return self::$gatewayNames;
    }

    /**
     * Validar si un gateway es válido.
     *
     * @param string $gateway Gateway a validar
     */
    public static function isValidGateway(string $gateway) : bool
    {
        return array_key_exists($gateway, self::$gatewayNames);
    }

    /**
     * Verificar si la cuenta pertenece a un usuario específico.
     *
     * @param string $userId ID del usuario
     */
    public function belongsToUser(string $userId) : bool
    {
        return $this->user_id === $userId;
    }

    /**
     * Obtener la información completa de la cuenta formateada.
     */
    public function getFormattedInfo() : string
    {
        return sprintf(
            '%s - %s (%s) - Doc: %s',
            $this->gateway_name,
            $this->user_name,
            $this->account_number,
            $this->document_number,
        );
    }

    /**
     * Accessor para obtener el nombre legible del gateway.
     */
    public function getGatewayNameAttribute() : string
    {
        return self::$gatewayNames[$this->gateway] ?? 'Desconocido';
    }

    /**
     * Verificar si la cuenta está activa.
     */
    public function isActive() : bool
    {
        return $this->status === true;
    }

    /**
     * Verificar si es una cuenta de Bancolombia.
     */
    public function isBancolombia() : bool
    {
        return $this->gateway === 'BANCOLOMBIA';
    }

    /**
     * Verificar si es una cuenta de Daviplata.
     */
    public function isDaviplata() : bool
    {
        return $this->gateway === 'DAVIPLATA';
    }

    /**
     * Verificar si es una cuenta de Nequi.
     */
    public function isNequi() : bool
    {
        return $this->gateway === 'NEQUI';
    }

    /**
     * Scope para obtener cuentas activas.
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
     * Scope para filtrar por número de cuenta.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAccountNumber($query, string $accountNumber)
    {
        return $query->where('account_number', $accountNumber);
    }

    /**
     * Scope para filtrar por número de documento.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDocument($query, string $documentNumber)
    {
        return $query->where('document_number', $documentNumber);
    }

    /**
     * Scope para filtrar por gateway.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
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
     * Relación con el usuario propietario.
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
