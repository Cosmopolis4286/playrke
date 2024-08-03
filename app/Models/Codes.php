<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Codes extends Model{
    protected $table = "codes_rke";
    protected $fillable = [
        'id','fecha','codigo','moneda','precio','usuario_id','win','premio'		
    ];

    public function User(){
        return $this->belongsTo('App\User','usuario_id');
    }
    
}