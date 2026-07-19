<?php

use App\Models\MovementType;
use Illuminate\Database\Migrations\Migration;

/**
 * Usa el modelo Eloquent (no DB::table()->insert()): MovementType usa
 * SnowflakeId, cuyo id se genera en el evento creating() del modelo — un
 * insert crudo por query builder deja `id` NULL y falla silenciosamente
 * con insertOrIgnore() (sin excepción, sin fila insertada).
 */
return new class extends Migration
{
    public function up(): void
    {
        MovementType::firstOrCreate(
            ['code' => 'BONUS_PRIZE'],
            [
                'label'             => 'Premio bono (no retirable)',
                'icon'              => 'fas fa-gift',
                'color'             => '#a855f7',
                'is_credit'         => true,
                'requires_approval' => false,
                'game_group'        => null,
                'sort_order'        => 35,
                'visible_roles'     => null,
                'is_system'         => true,
                'status'            => 'ACTIVE',
            ],
        );

        MovementType::forgetCatalogCache();
    }

    public function down(): void
    {
        MovementType::where('code', 'BONUS_PRIZE')->delete();

        MovementType::forgetCatalogCache();
    }
};
