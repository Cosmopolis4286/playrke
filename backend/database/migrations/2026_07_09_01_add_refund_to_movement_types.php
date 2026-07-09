<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('movement_types')->insertOrIgnore([
            'code'               => 'REFUND',
            'label'              => 'Reembolso',
            'icon'               => 'fas fa-undo',
            'color'              => '#0891b2',
            'is_credit'          => true,
            'requires_approval'  => false,
            'game_group'         => null,
            'sort_order'         => 70,
            'visible_roles'      => null,
            'is_system'          => true,
            'status'             => 'ACTIVE',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('movement_types')->where('code', 'REFUND')->delete();
    }
};
