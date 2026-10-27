<?php

namespace App\Models\Content;

use App\Traits\Auditable;
use App\Traits\HasActiveStatusScope;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

/**
 * Bloque de contenido editorial de la Landing pública (hero, jackpot, salas,
 * verificar, invita, ganadores, cta_final, footer) — administrable desde
 * /admin/landing-sections. El catálogo de section_key es fijo (is_removable),
 * solo se activa/desactiva y reordena, nunca se crea/destruye desde la UI.
 */
class LandingSection extends Model
{
    use Auditable, HasActiveStatusScope, SnowflakeId;

    protected $casts = [
        'id' => 'string',
        'section_key' => 'string',
        'label' => 'string',
        'content' => 'array',
        'sort_order' => 'integer',
        'status' => 'string',
        'is_removable' => 'boolean',
    ];

    protected $fillable = [
        'section_key',
        'label',
        'content',
        'sort_order',
        'status',
    ];

    public function getRouteKeyName() : string
    {
        return 'section_key';
    }
}
