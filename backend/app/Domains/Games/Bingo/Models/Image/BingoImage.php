<?php

namespace App\Domains\Games\Bingo\Models\Image;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class BingoImage extends Model
{
    use SnowflakeId;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = ['active' => 'Activa', 'inactive' => 'Inactiva'];
    public const TYPES = ['background' => 'Fondo', 'logo' => 'Logo', 'icon' => 'Icono', 'pattern' => 'Patrón', 'decoration' => 'Decoración'];
    protected $appends = ['status_description', 'type_description', 'file_exists', 'file_size', 'file_extension'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected $fillable = ['type', 'param', 'route', 'code', 'status'];

    protected $table = 'bingo_images';

    public function getFileExistsAttribute() : bool
    {
        return $this->route && File::exists(public_path($this->route));
    }

    public function getFileExtensionAttribute() : ?string
    {
        return $this->route ? pathinfo($this->route, PATHINFO_EXTENSION) : null;
    }

    public function getFileNameAttribute() : ?string
    {
        return $this->route ? basename($this->route) : null;
    }

    public function getFileSizeAttribute() : ?int
    {
        return $this->file_exists ? File::size(public_path($this->route)) : null;
    }

    public function getFormattedFileSize() : string
    {
        $size = $this->file_size;
        if (! $size) {
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

    public function getFullUrlAttribute() : ?string
    {
        return $this->route ? asset($this->route) : null;
    }

    public function getIsActiveAttribute() : bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getIsInactiveAttribute() : bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function getStatusDescriptionAttribute() : string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getTypeDescriptionAttribute() : string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isValid() : bool
    {
        return ! empty($this->type) && ! empty($this->route) && $this->file_exists && $this->isValidImageFile();
    }

    public function isValidImageFile() : bool
    {
        return in_array(strtolower($this->file_extension ?: ''), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    public function scopeByExtension($query, string $ext)
    {
        return $query->where('route', 'like', "%.{$ext}");
    }

    public function scopeByParam($query, string $param)
    {
        return $query->where('param', $param);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }
}
