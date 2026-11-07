<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Bingo;

use App\Domains\Games\Bingo\Services\Payment\BingoCardPaymentReviewService;
use PHPUnit\Framework\TestCase;

class BingoCardPaymentReviewPolicyTest extends TestCase
{
    public function test_suspended_prizes_block_automatic_payment_rejection_and_lot_refund() : void
    {
        $this->assertContains(
            'suspended',
            BingoCardPaymentReviewService::REJECTION_BLOCKING_PRIZE_STATUSES,
        );
    }

    public function test_completed_and_unresolved_prize_states_are_all_blocking() : void
    {
        $this->assertSame(
            ['waiting', 'processing', 'claimed', 'suspended', 'disputed'],
            BingoCardPaymentReviewService::REJECTION_BLOCKING_PRIZE_STATUSES,
        );
    }
}
