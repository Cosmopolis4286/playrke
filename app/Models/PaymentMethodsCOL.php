<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethodsCOL extends Model
{
	protected $table = 'payment_methods_col';
	protected $fillable = ['gateway', 'user_name', 'acc_number', 'document_number', 'user'];

	public function user()
	{
		return $this->belongsTo(User::class, 'user');
	}

	/**
	 * Accesor para el campo 'gateway' para devolver valores legibles.
	 */
	public function getGatewayAttribute($value)
	{
		$gateways = [
			'B' => 'Bancolombia',
			'D' => 'Daviplata',
			'N' => 'Nequi'
		];

		return $gateways[$value] ?? 'Desconocido';
	}

	public static function getById($id, $fields = ['*'])
	{
		return self::select($fields)->where('id', $id)->first();
	}

	public static function existsRegister($gateway, $userName, $accNumber, $documentNumber, $user = false)
	{
		return self::where([
			['gateway', $gateway],
			['user_name', $userName],
			['acc_number', $accNumber],
			['document_number', $documentNumber]
		])->when($user, fn($query) => $query->where('user', '<>', $user))->exists();
	}
}
