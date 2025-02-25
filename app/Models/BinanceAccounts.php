<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BinanceAccounts extends Model
{
	protected $table = "binance_accounts";
	protected $fillable = ['account_type', 'data_value', 'user', 'status'];

	public const TYPES = [
		['id' => 'E', 'description' => 'E-mail'],
		['id' => 'P', 'description' => 'Num. Celular'],
		['id' => 'B', 'description' => 'Id de Binance'],
	];

	protected $appends = ['account_type_description'];

	public function getAccountTypeAttribute()
	{
		$accountType = collect(self::TYPES)->firstWhere('id', $this->account_type);
		return $accountType['description'] ?? 'Desconocido';
	}

	public function User()
	{
		return $this->belongsTo(User::class, 'user');
	}

	public static function getAccountTypes($excludeId = null)
	{
		if ($excludeId) {
			return array_filter(self::TYPES, function ($type) use ($excludeId) {
				return $type['id'] !== $excludeId;
			});
		}

		return self::TYPES;
	}

	public static function getById($id, $fields = ['*'])
	{
		return self::select($fields)->where('id', $id)->first();
	}

	public static function existsAccount($accountType, $dataValue, $user = false)
	{
		return self::where([['account_type', $accountType], ['data_value', $dataValue]])
			->when($user, fn($query) => $query->where('user', '<>', $user))->exists();
	}
}
