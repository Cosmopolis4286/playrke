<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Luck extends Model{
    protected $table = "sorteos";
    protected $fillable = [
        'id','fecha','hora','participants','winners','premios','certificate'
    ];

    public function User(){
        return $this->belongsTo('App\User','usuario_id');
    }
    
}