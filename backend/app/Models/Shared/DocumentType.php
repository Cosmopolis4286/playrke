<?php

namespace App\Models\Shared;

use App\Casts\AsActiveStatusEnum;
use App\Models\Country;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentType extends Model
{
    use SnowflakeId;
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'status' => AsActiveStatusEnum::class,
    ];

    protected $fillable = [
        'code',
        'description',
        'country_id',
        'status',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'document_types';

    public function country() : BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
