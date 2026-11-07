<?php

namespace Tests\Unit\Domains\Bingo;

use App\Contracts\NotificationRepositoryInterface;
use App\Domains\Games\Bingo\Services\Payment\BingoCardPaymentReviewService;
use App\Domains\Games\Bingo\Services\Payment\BingoPendingPaymentStartService;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Event;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class BingoPendingPaymentNotificationTest extends TestCase
{
    public function test_notification_delivery_failure_is_reported_as_retryable() : void
    {
        Event::fake();

        $repository = $this->createMock(NotificationRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('create')
            ->willThrowException(new RuntimeException('notification transport unavailable'));

        $service = $this->makeService($repository);
        $method = new ReflectionMethod($service, 'notify');

        $this->assertFalse($method->invoke($service, 'reviewer-1', 'Pagos por revisar', 'La sala inicia pronto'));
    }

    public function test_successful_notification_delivery_is_reported() : void
    {
        Event::fake();

        $repository = $this->createMock(NotificationRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('create')
            ->willReturn(new Notification());

        $service = $this->makeService($repository);
        $method = new ReflectionMethod($service, 'notify');

        $this->assertTrue($method->invoke($service, 'reviewer-1', 'Pagos por revisar', 'La sala inicia pronto'));
    }

    private function makeService(NotificationRepositoryInterface $repository) : BingoPendingPaymentStartService
    {
        $notifications = new NotificationService($repository);
        $review = new BingoCardPaymentReviewService($notifications);

        return new BingoPendingPaymentStartService($review, $notifications);
    }
}
