<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoMovil extends Model
{
	protected $table = 'pago_movil';
	protected $fillable = ['document_type', 'document', 'phone', 'bank', 'user'];

	public function User()
	{
		return $this->belongsTo(User::class, 'user');
	}

	public function DocumentType()
	{
		return $this->belongsTo(DocumentType::class, 'document_type');
	}

	public function Bank()
	{
		return $this->belongsTo(Banks_VEN::class, 'bank');
	}

	public static function getUserByPagoMovil($documentType, $document, $bank, $phone)
	{
		return self::where('document_type', $documentType)->where('document', $document)
			->where('bank', $bank)->where('phone', $phone)->value('user');
	}

	public static function existsPagoMovil($documentType, $document, $bank, $phone)
	{
		return self::where('document_type', $documentType)->where('document', $document)
			->where('bank', $bank)->where('phone', $phone)->exists();
	}
}
