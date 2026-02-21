<?php

namespace App\Models\Games\Bingo\Draw;

use App\Models\Games\Bingo\Room\BingoRoom;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los códigos de sorteo de bingo
 *
 * Representa los códigos de identificación de sorteos de bingo.
 * Incluye número y símbolo asociado a cada sala de bingo.
 *
 * @package App\Models\Bingo\Draw
 * @author Sistema PlayRKE
 * @version 1.0
 *
 * @property string $id ID único del código
 * @property string $num Número del código
 * @property string $sym Símbolo del código
 * @property string $room ID de la sala de bingo
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 *
 * @property-read BingoRoom $room Sala de bingo asociada
 */
class BingoDrawCode extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_draws_code';

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'num',
        'sym',
        'room',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos que se agregan automáticamente al modelo
     */
    protected $appends = ['full_code', 'formatted_code'];

    /**
     * Relación con la sala de bingo
     *
     * @return BelongsTo<BingoRoom, BingoDrawCode>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    /**
     * Scope para obtener códigos por sala
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $roomId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRoom($query, string $roomId)
    {
        return $query->where('room', $roomId);
    }

    /**
     * Scope para obtener códigos por número
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $num
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByNumber($query, string $num)
    {
        return $query->where('num', $num);
    }

    /**
     * Scope para obtener códigos por símbolo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sym
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySymbol($query, string $sym)
    {
        return $query->where('sym', $sym);
    }

    /**
     * Scope para obtener códigos por patrón de número
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $pattern
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByNumberPattern($query, string $pattern)
    {
        return $query->where('num', 'like', $pattern);
    }

    /**
     * Scope para obtener códigos por patrón de símbolo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $pattern
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySymbolPattern($query, string $pattern)
    {
        return $query->where('sym', 'like', $pattern);
    }

    /**
     * Scope para obtener códigos únicos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnique($query)
    {
        return $query->distinct(['num', 'sym']);
    }

    /**
     * Accessor para obtener el código completo (número + símbolo)
     *
     * @return string
     */
    public function getFullCodeAttribute(): string
    {
        return $this->num . $this->sym;
    }

    /**
     * Accessor para obtener el código formateado
     *
     * @return string
     */
    public function getFormattedCodeAttribute(): string
    {
        return sprintf('%s-%s', $this->num, $this->sym);
    }

    /**
     * Accessor para verificar si es un código válido
     *
     * @return bool
     */
    public function getIsValidAttribute(): bool
    {
        return !empty($this->num) && !empty($this->sym) && $this->isValidFormat();
    }

    /**
     * Verificar si el formato del código es válido
     *
     * @return bool
     */
    public function isValidFormat(): bool
    {
        // Validar que el número sea numérico y tenga máximo 4 dígitos
        if (!is_numeric($this->num) || strlen($this->num) > 4) {
            return false;
        }

        // Validar que el símbolo sea una letra mayúscula
        if (!preg_match('/^[A-Z]$/', $this->sym)) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si el código es único
     *
     * @return bool
     */
    public function isUnique(): bool
    {
        $query = static::where('num', $this->num)
            ->where('sym', $this->sym);

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return !$query->exists();
    }
}
