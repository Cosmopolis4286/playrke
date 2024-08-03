<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CodTef extends Model{
    protected $table = "cod_telefonia";
    protected $fillable = ['id','codigo', 'pais'];
    public function pagoMovil(){
        return $this->hasMany('App\Models\PagoMovil','pagomovil_id');
    }
}