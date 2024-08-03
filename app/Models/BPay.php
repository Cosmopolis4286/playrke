<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BPay extends Model
{
	protected $table = "accounts_bpay";
	protected $fillable = [
		'pay_id', 'email', 'user'
	];

	public function User()
	{
		return $this->belongsTo('App\Models\User', 'user');
	}
}
