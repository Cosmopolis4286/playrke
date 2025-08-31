<?php

namespace App\Models\Payments\Methods\Venezuela;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\User\Shared\DocumentType;
use App\Models\Banks\BanksVenezuela;

class PagoMovil extends Model
{
	protected $table = 'pago_movil';

	protected $fillable = [
		'document_type_id',
		'document',
		'phone',
		'bank_id',
		'user_id'
	];

	/* Relaciones */
	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}

	public function documentType()
	{
		return $this->belongsTo(DocumentType::class, 'document_type_id');
	}

	public function bank()
	{
		return $this->belongsTo(BanksVenezuela::class, 'bank_id');
	}

	/* Métodos helpers */
	public static function getUserByPagoMovil($documentType, $document, $bank, $phone)
	{
		return self::where([
			['document_type_id', $documentType],
			['document', $document],
			['bank_id', $bank],
			['phone', $phone],
		])->value('user_id');
	}

	public static function existsPagoMovil($documentType, $document, $bank, $phone, $excludeUser = null)
	{
		return self::where([
			['document_type_id', $documentType],
			['document', $document],
			['bank_id', $bank],
			['phone', $phone],
		])->when($excludeUser, fn($query) => $query->where('user_id', '<>', $excludeUser))
			->exists();
	}
}
