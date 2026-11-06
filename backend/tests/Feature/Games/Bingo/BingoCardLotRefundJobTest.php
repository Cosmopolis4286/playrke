<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Jobs\BingoCardLotRefundJob;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Domains\Wallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BingoCardLotRefundJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_pending_failure_when_lot_was_already_refunded() : void
    {
        $roomId = '970000000000000101';
        $lotId = '970000000000000102';
        $failureId = '970000000000000103';

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::table('bingo_rooms')->insert([
                'id' => $roomId,
                'uuid_code' => 'refund-idempotency-test',
                'status_id' => '970000000000000104',
                'creator_user_id' => '970000000000000105',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('bingo_card_lots')->insert([
                'id' => $lotId,
                'room_id' => $roomId,
                'user_id' => '970000000000000105',
                'room_user_id' => '970000000000000106',
                'currency_id' => '970000000000000107',
                'card_count' => 1,
                'card_price' => 5,
                'exchange_rate' => 1,
                'sale_amount' => 5,
                'status' => 'REFUNDED',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('bingo_lot_refund_failures')->insert([
                'id' => $failureId,
                'room_id' => $roomId,
                'lot_id' => $lotId,
                'user_id' => '970000000000000105',
                'currency_id' => '970000000000000107',
                'amount' => 5,
                'error_message' => 'Fallo anterior antes de confirmar el estado final',
                'status' => 'PENDING',
                'attempts' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            (new BingoCardLotRefundJob($lotId, $roomId))->handle(
                app(WalletService::class),
                app(BingoRoomAuditLogger::class),
            );

            $failure = DB::table('bingo_lot_refund_failures')->where('id', $failureId)->first();

            $this->assertSame('RESOLVED', $failure->status);
            $this->assertNotNull($failure->resolved_at);
            $this->assertSame('REFUNDED', DB::table('bingo_card_lots')->where('id', $lotId)->value('status'));
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
