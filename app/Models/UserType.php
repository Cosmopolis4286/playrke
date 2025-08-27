<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo UserType
 *
 * Representa los tipos de usuario dentro del sistema (ej: admin, seller, customer, etc.)
 *
 * @property int         $id
 * @property string      $description
 * @property string      $prefix
 * @property int         $status
 */
class UserType extends Model
{
    /** @var string */
    protected $table = "user_type";

    /** @var array<int,string> */
    protected $fillable = ['id', 'description', 'prefix', 'status'];
}
