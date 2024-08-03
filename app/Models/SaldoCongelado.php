<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoCongelado extends Model
{
	protected $table = "frozen_balance";
	protected $fillable = [
		'monto', 'recarga_id', 'user'
	];

	public function recargaCongelada()
	{
		return $this->belongsTo('App\Models\Recarga', 'recarga_id');
	}
}
