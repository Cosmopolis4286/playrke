<?php

namespace Tests\\Unit\\Domains\\Bingo;

use App\\Domains\\Games\\Bingo\\Services\\Payment\\BingoCardPaymentReviewService;
use App\\Domains\\Games\\Bingo\\Services\\Payment\\BingoPendingPaymentStartService;
use App\\Services\\NotificationService;
use PHPUnit\\Framework\\TestCase;
use ReflectionMethod;
use RuntimeException;

class BingoPendingPaymentNotificationTest extends TestCase
{
    public function test_notification_delivery_failure_is_reported_as_retryable() : void
    {
        $review = $this->createMock(BingoCardPaymentReviewService::class);
        $notifications = $this->createMock(NotificationService::class);
        $notifications->expects($this->once())
            ->method('create')
            ->willThrowException(new RuntimeException('notification transport unavailable'));

        $service = new BingoPendingPaymentStartService($review, $notifications);
        $method = new ReflectionMethod($service, 'notify');

        $this->assertFalse($method->invoke($service, 'reviewer-1', 'Pending payments', 'Room starts soon'));
    }

    public function test_successful_notification_delivery_is_reported() : void
    {
        $review = $this->createMock(BingoCardPaymentReviewService::class);
        $notifications = $this->createMock(NotificationService::class);
        $notifications->expects($this->once())->method('create');

        $service = new BingoPendingPaymentStartService($review, $notifications);
        $method = new ReflectionMethod($service, 'notify');

        $this->assertTrue($method->invoke($service, 'reviewer-1', 'Pending payments', 'Room starts soon'));
    }
}
