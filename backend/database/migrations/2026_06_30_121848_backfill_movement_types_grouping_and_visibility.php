<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const VALUES = [
        'DEPOSIT'        => ['game_group' => null,     'sort_order' => 10, 'visible_roles' => null],
        'WITHDRAWAL'     => ['game_group' => null,     'sort_order' => 20, 'visible_roles' => null],
        'PURCHASE'       => ['game_group' => 'BINGO',  'sort_order' => 30, 'visible_roles' => null],
        'PRIZE'          => ['game_group' => 'BINGO',  'sort_order' => 40, 'visible_roles' => null],
        'TRIVIA_ENTRY'   => ['game_group' => 'TRIVIA', 'sort_order' => 50, 'visible_roles' => null],
        'RKE_COMMISSION' => ['game_group' => null,     'sort_order' => 60, 'visible_roles' => ['admin', 'superadmin', 'commerce', 'customer']],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::VALUES as $code => $attrs) {
            DB::table('movement_types')->where('code', $code)->update([
                'game_group'    => $attrs['game_group'],
                'sort_order'    => $attrs['sort_order'],
                'visible_roles' => $attrs['visible_roles'] !== null ? json_encode($attrs['visible_roles']) : null,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('movement_types')->whereIn('code', array_keys(self::VALUES))->update([
            'game_group'    => null,
            'sort_order'    => 0,
            'visible_roles' => null,
        ]);
    }
};
