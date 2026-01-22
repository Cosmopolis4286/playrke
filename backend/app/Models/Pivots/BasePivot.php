<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\Pivot;

abstract class BasePivot extends Pivot
{
    use SoftDeletes;
    use Auditable;

    /**
     * Evita asignación masiva accidental
     */
    protected $guarded = [];
}
