<?php

namespace App\Models\Games\Bingo\Image;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

/**
 * Modelo para las imágenes de bingo
 *
 * Representa las imágenes utilizadas en el sistema de bingo.
 * Incluye información sobre tipo, parámetros, ruta del archivo y estado.
 *
 * @package App\Models\Bingo\Image
 * @author Sistema PlayRKE
 * @version 1.0
 *
 * @property string $id ID único de la imagen
 * @property string $type Tipo de imagen
 * @property string $param Parámetro asociado
 * @property string $route Ruta del archivo de imagen
 * @property string $code Código de identificación
 * @property string $status Estado de la imagen (active, inactive)
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 */
class BingoImage extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_images';

    /**
     * Estados disponibles para las imágenes
     */
    public const STATUSES = [
        'active' => 'Activa',
        'inactive' => 'Inactiva',
    ];

    /**
     * Tipos de imágenes disponibles
     */
    public const TYPES = [
        'background' => 'Fondo',
        'logo' => 'Logo',
        'icon' => 'Icono',
        'pattern' => 'Patrón',
        'decoration' => 'Decoración',
    ];

    /**
     * Estados como constantes
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'type',
        'param',
        'route',
        'code',
        'status',
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
    protected $appends = [
        'status_description',
        'type_description',
        'file_exists',
        'file_size',
        'file_extension'
    ];

    /**
     * Scope para obtener imágenes por tipo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para obtener imágenes por estado
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para obtener imágenes activas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para obtener imágenes inactivas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope para obtener imágenes por parámetro
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $param
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByParam($query, string $param)
    {
        return $query->where('param', $param);
    }

    /**
     * Scope para obtener imágenes por código
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope para obtener imágenes por extensión
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $extension
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByExtension($query, string $extension)
    {
        return $query->where('route', 'like', "%.{$extension}");
    }

    /**
     * Accessor para obtener la descripción del estado
     *
     * @return string
     */
    public function getStatusDescriptionAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Accessor para obtener la descripción del tipo
     *
     * @return string
     */
    public function getTypeDescriptionAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Accessor para verificar si el archivo existe
     *
     * @return bool
     */
    public function getFileExistsAttribute(): bool
    {
        return $this->route && File::exists(public_path($this->route));
    }

    /**
     * Accessor para obtener el tamaño del archivo
     *
     * @return int|null Tamaño en bytes
     */
    public function getFileSizeAttribute(): ?int
    {
        if (!$this->file_exists) {
            return null;
        }

        return File::size(public_path($this->route));
    }

    /**
     * Accessor para obtener la extensión del archivo
     *
     * @return string|null
     */
    public function getFileExtensionAttribute(): ?string
    {
        return $this->route ? pathinfo($this->route, PATHINFO_EXTENSION) : null;
    }

    /**
     * Accessor para verificar si está activa
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Accessor para verificar si está inactiva
     *
     * @return bool
     */
    public function getIsInactiveAttribute(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Accessor para obtener la URL completa de la imagen
     *
     * @return string|null
     */
    public function getFullUrlAttribute(): ?string
    {
        return $this->route ? asset($this->route) : null;
    }

    /**
     * Accessor para obtener el nombre del archivo
     *
     * @return string|null
     */
    public function getFileNameAttribute(): ?string
    {
        return $this->route ? basename($this->route) : null;
    }

    /**
     * Verificar si la imagen es válida
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (empty($this->type) || empty($this->route)) {
            return false;
        }

        return $this->file_exists && $this->isValidImageFile();
    }

    /**
     * Verificar si es un archivo de imagen válido
     *
     * @return bool
     */
    public function isValidImageFile(): bool
    {
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        $extension = strtolower($this->file_extension ?: '');

        return in_array($extension, $validExtensions);
    }

    /**
     * Obtener el tamaño formateado del archivo
     *
     * @return string
     */
    public function getFormattedFileSize(): string
    {
        $size = $this->file_size;

        if (!$size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
