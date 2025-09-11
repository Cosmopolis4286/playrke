<?php

namespace App\Models\Countries;

use App\Models\Users\Roles\Player\External\ExternalPlayers;
use App\Models\Users\Roles\Seller\Seller;
use BradieTilley\Snowflakes\Eloquent\HasSnowflake;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasSnowflake; // Habilita IDs Snowflake

    protected $table = "countries";

    // Indicar que la PK no es autoincremental y es string
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'iso_code_2',
        'iso_code_3',
        'status'
    ];

    /**
     * Relación: un país tiene muchos vendedores.
     */
    public function sellers()
    {
        return $this->hasMany(Seller::class, 'country_id');
    }

    /**
     * Relación: un país tiene muchos jugadores externos.
     */
    public function externalPlayers()
    {
        return $this->hasMany(ExternalPlayers::class, 'country_id');
    }
}
