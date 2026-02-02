<?php

namespace App\Models\Shared;

use App\Models\Country;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentType extends Model
{
    use SnowflakeId;

    protected $table = 'document_types';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'description',
        'country_id',
        'status'
    ];

    protected $casts = [
        'id' => 'string',
        'status' => 'boolean'
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
