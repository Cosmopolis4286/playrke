<?php

namespace App\Models\Directories\Fiat\Venezuela;

use App\Models\User;
use App\Models\Banks\BankVenezuela;
use App\Models\Shared\DocumentType;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para la gestión de Pago Móvil de Venezuela.
 *
 * Representa los registros de pago móvil asociados a usuarios,
 * incluyendo información de documento, banco y teléfono.
 */
class PagoMovil extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'pago_movil_venezuela';

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'holder_name',      // Nombre del titular
        'document_type_id', // ID del tipo de documento
        'document_number',  // Número de documento
        'phone',            // Número de teléfono
        'bank_id',          // ID del banco venezolano
        'user_id',          // ID del usuario propietario
    ];

    // ========================
    // Casting
    // ========================
    protected $casts = [
        'id' => 'string',
        'document_type_id' => 'string',
    ];

    /**
     * Accessor para el label de status con badge HTML
     */
    public function getDocumentAttribute(): string
    {
        return sprintf('%s-%s', $this->documentType->code ?? 'DOC', $this->document_number);
    }

    /**
     * Accessor para el badge de status con badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'ACTIVE' => '<span class="badge badge-success">Activo</span>',
            'INACTIVE' => '<span class="badge badge-secondary">Inactivo</span>',
            'BLOCKED' => '<span class="badge badge-danger">Bloqueado</span>',
            'DELETED' => '<span class="badge badge-dark">Eliminado</span>',
            default => '<span class="badge badge-warning">Desconocido</span>'
        };
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
     * Relación con el tipo de documento.
     *
     * @return BelongsTo
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    /**
     * Relación con el banco venezolano.
     *
     * @return BelongsTo
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(BankVenezuela::class, 'bank_id');
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
     * Scope para filtrar por número de teléfono.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $phone
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }

    /**
     * Scope para filtrar por tipo de documento.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $documentTypeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDocumentType($query, string $documentTypeId)
    {
        return $query->where('document_type_id', $documentTypeId);
    }

    /**
     * Obtener la información completa del pago móvil formateada.
     *
     * @return string
     */
    public function getFormattedInfo(): string
    {
        return sprintf(
            '%s: %s - %s - Tel: %s',
            $this->documentType->code ?? 'DOC',
            $this->document_number,
            $this->bank->name ?? 'Banco',
            $this->phone
        );
    }

    /**
     * Verificar si el pago móvil pertenece a un usuario específico.
     *
     * @param string $userId ID del usuario
     * @return bool
     */
    public function belongsToUser(string $userId): bool
    {
        return $this->user_id === $userId;
    }

    /**
     * Verificar si el número de teléfono es válido (formato venezolano).
     *
     * @return bool
     */
    public function hasValidPhone(): bool
    {
        // Formato básico venezolano: 04XX-XXXXXXX o 02XX-XXXXXXX
        return preg_match('/^(04|02)\d{2}-?\d{7}$/', $this->phone);
    }
}
