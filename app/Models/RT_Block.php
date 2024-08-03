<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RT_Block extends Model{
    protected $table = "rt_cryptos";
    protected $fillable = [
        'id','fecha','crypto_mon','red','cantidad','txid','estatus','motivo','usuario_id'	
    ];

    public function User(){
        return $this->belongsTo('App\User','usuario_id');
    }
    
}