<?php

namespace Tests\Unit\Domains\Bingo;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Domains\Games\Bingo\Services\Winner\BingoPrizePayoutService;
use App\Services\Games\GamePrizeService;
use Mockery;
use PHPUnit\Framework\TestCase;

class BingoExternalPlayerPrizeOwnershipTest extends TestCase
{
    protected function tearDown() : void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_external_buyer_prize_is_not_sent_to_sellers_wallet() : void
    {
        // GamePrizeService es final; una WalletService simulada permite
        // comprobar que la ruta externa no intenta llegar al pago de wallet.
        $walletService = Mockery::mock(WalletService::class);
        $walletService->shouldNotReceive('recordPrize');
        $gamePrizeService = new GamePrizeService($walletService);

        $auditLogger = Mockery::mock(BingoRoomAuditLogger::class);
        $auditLogger->shouldNotReceive('log');

        $prize = new BingoCardPrize([
            'id' => '900000000000000001',
            'card_lot_id' => '900000000000000002',
            'creator_user_id' => '900000000000000003',
            'seller_user_id' => '900000000000000003',
            'external_player_id' => '900000000000000004',
            'prize_status' => 'suspended',
        ]);

        $prize->setRelation('room', null);

        $service = new BingoPrizePayoutService($gamePrizeService, $auditLogger);
        $service->payout($prize);

        $this->assertSame('suspended', $prize->prize_status);
        $this->assertSame('900000000000000004', $prize->external_player_id);
    }

    public function test_external_prize_payment_attributes_do_not_name_seller_as_payee() : void
    {
        $prize = new BingoCardPrize([
            'creator_user_id' => '900000000000000003',
            'external_player_id' => '900000000000000004',
            'local_amount' => 25.50,
        ]);

        $attributes = $prize->buildPaymentAttributes(null, 'pending', 'Entrega externa pendiente');

        $this->assertSame('900000000000000004', $attributes['external_player_id']);
        $this->assertNull($attributes['user_id']);
        $this->assertSame('900000000000000003', $attributes['creator_user_id']);
    }

    public function test_internal_prize_payment_attributes_keep_registered_winner_as_payee() : void
    {
        $prize = new BingoCardPrize([
            'creator_user_id' => '900000000000000003',
            'local_amount' => 25.50,
        ]);

        $attributes = $prize->buildPaymentAttributes(null, 'pending', null);

        $this->assertSame('900000000000000003', $attributes['user_id']);
        $this->assertNull($attributes['external_player_id']);
    }
}
