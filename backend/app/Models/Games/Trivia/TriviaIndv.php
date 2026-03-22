<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TriviaIndv extends Model{

	protected $table = "triv_indv_quest";
	protected $fillable = [
		'question', 'resp_correct','opc_1','opc_2', 'opc_3', 'img'
	];

    public $timestamps = false;
    /* RELACIONES */
    // Relación question (1:n)
    public function requestion(){
    	return $this->hasMany('App\Models\question','id_trivia');
    }
   
    // /Relación question (1:n)
    /* /RELACIONES */
}