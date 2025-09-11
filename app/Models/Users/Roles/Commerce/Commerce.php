<?php

namespace App\Models\Users\Roles\Commerce;

use App\Models\Countries\Country;
use App\Models\Users\Roles\Seller\Seller;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commerce extends Model
{
  protected $table = "commerces";

  /**
   * Campos asignables masivamente
   */
  protected $fillable = [
    'name',
    'country_id',   // FK Country
    'currency_id',
    'phone',
    'user_id',      // FK User
    'commission',
    'rke_code_id',
    'status'
  ];

  /**
   * Retorna el label de status con badge HTML
   */
  public function getStatusLabelAttribute(): string
  {
    if ($this->status == 1) {
      return '<span class="badge badge-success">Activo</span>';
    }
    return '<span class="badge badge-danger">Inactivo</span>';
  }

  /**
   * Relación con Country
   */
  public function Country(): BelongsTo
  {
    return $this->belongsTo(Country::class, 'country_id');
  }

  /**
   * Relación con User
   */
  public function User(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * Relación con Sellers
   */
  public function Sellers(): HasMany
  {
    return $this->hasMany(Seller::class, 'commerce_id', 'id');
  }

  // ---------------------------
  // Métodos de búsqueda rápida
  // ---------------------------

  public static function getByUserId($user, $fields = ['*']): ?self
  {
    return self::select($fields)->where('user', $user)->first();
  }

  public static function getById($id, $fields = ['*']): ?self
  {
    return self::select($fields)->where('id', $id)->first();
  }

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }

  public static function getValueByUserId($user, $value)
  {
    return self::where('user', $user)->value($value);
  }

  public static function getByCodeRKE($code, $fields = ['*']): ?self
  {
    return self::select($fields)->where('rke_code', $code)->first();
  }

  public static function getValueByCodeRKE($code, $value)
  {
    return self::where('rke_code', $code)->value($value);
  }

  public static function getByCodeUUID($code, $fields = ['*']): ?self
  {
    return self::select($fields)->where('uuid_code', $code)->first();
  }

  public static function getValueByCodeUUID($code, $value)
  {
    return self::where('uuid_code', $code)->value($value);
  }

  /**
   * Verifica si existe un valor en alguna columna
   */
  public static function existsValue(string $column, $value): bool
  {
    return self::where($column, $value)->exists();
  }

  /**
   * Retorna todos los comercios activos
   */
  public static function getActiveCommerces($fields = ['*'])
  {
    return self::where('status', 1)->get($fields);
  }
}
