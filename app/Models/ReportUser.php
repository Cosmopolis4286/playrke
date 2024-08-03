<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ReportUser extends Model{
	protected $table = "report_problem";
	protected $fillable = ['usuario_id','problem'];

	// Relación user (n:1)
	public function User(){
		return $this->belongsTo('App\User','user');
	}
}