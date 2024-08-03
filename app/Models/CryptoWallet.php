<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryptoWallet extends Model
{
	protected $table = "crypto_wallets";
	protected $fillable = [
		'crypto_mon', 'crypto_red', 'crypto_direcc', 'usuario_id',
	];

	// Relación user (n:1)
	public function User()
	{
		return $this->belongsTo('App\Models\User', 'user');
	}
}
