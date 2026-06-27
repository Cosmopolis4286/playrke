<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill: filas PRIZE existentes sin prize_type en metadata -> DIGITAL
        DB::table('wallet_movements')
            ->where('type', 'PRIZE')
            ->whereNull('metadata')
            ->update(['metadata' => json_encode(['prize_type' => 'DIGITAL'])]);

        // Migrar TRIVIA_PRIZE -> PRIZE, completando metadata con prize_type=DIGITAL
        // y preservando game_id si ya existía en metadata.
        DB::table('wallet_movements')
            ->where('type', 'TRIVIA_PRIZE')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $metadata = $row->metadata ? json_decode($row->metadata, true) : [];
                    $metadata['prize_type'] = $metadata['prize_type'] ?? 'DIGITAL';

                    DB::table('wallet_movements')
                        ->where('id', $row->id)
                        ->update([
                            'type'     => 'PRIZE',
                            'metadata' => json_encode($metadata),
                        ]);
                }
            });

        DB::statement("ALTER TABLE wallet_movements MODIFY COLUMN type ENUM('DEPOSIT','WITHDRAWAL','PURCHASE','PRIZE','TRIVIA_ENTRY','RKE_COMMISSION') NOT NULL COMMENT 'Tipo de movimiento'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE wallet_movements MODIFY COLUMN type ENUM('DEPOSIT','WITHDRAWAL','PURCHASE','PRIZE','TRIVIA_ENTRY','TRIVIA_PRIZE','RKE_COMMISSION') NOT NULL COMMENT 'Tipo de movimiento'");
    }
};
