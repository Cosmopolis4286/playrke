<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function down(): void
    {
        // El backfill es irreversible: deshacerlo podría atribuir premios al vendedor.
    }

    public function up(): void
    {
        // external_player_id ya existe desde la migración 2026_11_03_100000.
        $participations = DB::table('external_player_participations')
            ->selectRaw('JSON_UNQUOTE(participable_id) as card_id, MIN(external_player_id) as external_player_id')
            ->where('participable_type', \App\Domains\Games\Bingo\Models\Card\BingoCard::class)
            ->groupByRaw('JSON_UNQUOTE(participable_id)')
            ->havingRaw('COUNT(DISTINCT external_player_id) = 1');

        DB::table('bingo_card_prizes')
            ->joinSub($participations, 'external_card_owners', function ($join) {
                $join->on('bingo_card_prizes.winning_card_id', '=', 'external_card_owners.card_id');
            })
            ->whereNull('bingo_card_prizes.external_player_id')
            ->update(['bingo_card_prizes.external_player_id' => DB::raw('external_card_owners.external_player_id')]);

        DB::table('bingo_card_prize_payments')
            ->join('bingo_card_prizes', 'bingo_card_prize_payments.card_prize_id', '=', 'bingo_card_prizes.id')
            ->whereNull('bingo_card_prize_payments.external_player_id')
            ->whereNotNull('bingo_card_prizes.external_player_id')
            ->update(['bingo_card_prize_payments.external_player_id' => DB::raw('bingo_card_prizes.external_player_id')]);

        // Los premios externos reclamados o con pago completado requieren conciliación.
        DB::table('bingo_card_prizes')
            ->whereIn('prize_status', ['claimed', 'waiting', 'processing', 'suspended'])
            ->whereIn('winning_card_id', DB::table('external_player_participations')
                ->where('participable_type', \App\Domains\Games\Bingo\Models\Card\BingoCard::class)
                ->selectRaw('JSON_UNQUOTE(participable_id)'))
            ->where(function ($query) {
                $query->where('prize_status', 'claimed')
                    ->orWhereExists(function ($payments) {
                        $payments->selectRaw('1')
                            ->from('bingo_card_prize_payments')
                            ->whereColumn('bingo_card_prize_payments.card_prize_id', 'bingo_card_prizes.id')
                            ->where('bingo_card_prize_payments.payment_status', 'completed');
                    });
            })
            ->update(['prize_status' => 'disputed']);
    }
};
