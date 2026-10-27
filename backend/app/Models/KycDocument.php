<?php

namespace App\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    use SnowflakeId;

    public const DOCUMENT_TYPES = ['dni', 'passport', 'driving_license', 'residence_permit'];
    public const STATUS_APPROVED = 'approved';

    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';
    public $incrementing = false;

    protected $casts = [
        'reviewed_at' => 'datetime',
        'id' => 'string',
        'user_id' => 'string',
        'reviewed_by' => 'string',
    ];

    protected $fillable = [
        'user_id',
        'document_type',
        'document_number',
        'front_path',
        'back_path',
        'selfie_path',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];
    protected $keyType = 'string';

    protected $table = 'kyc_documents';

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
