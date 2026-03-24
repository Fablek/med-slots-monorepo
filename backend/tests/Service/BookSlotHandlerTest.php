<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Booking\Command\BookSlotCommand;
use App\Booking\Command\BookSlotResult;
use App\Entity\TimeSlot;
use App\Booking\Event\SlotBookedEvent;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookSlotHandlerTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private MessageBusInterface|MockObject $bus;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private BookSlotHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->handler = new BookSlotHandler(
            $this->entityManager,
            $this->bus,
            $this->eventDispatcher
        );
    }

    public function testBookSlotSuccessfully(): void
    {
        $slotId = Uuid::v4();
        $patientEmail = 'patient@example.com';

        $slot = $this->createMock(TimeSlot::class);
        $slot->expects($this->once())
            ->method('isBooked')
            ->willReturn(false);
        $slot->expects($this->once())
            ->method('setIsBooked')
            ->with(true)
            ->willReturnSelf();
        $slot->expects($this->once())
            ->method('setPatientEmail')
            ->with($patientEmail)
            ->willReturnSelf();
        $slot->expects($this->once())
            ->method('getId')
            ->willReturn($slotId);
        $slot->expects($this->once())
            ->method('getStartTime')
            ->willReturn(new \DateTimeImmutable('2025-01-15 09:00:00'));
        $slot->expects($this->once())
            ->method('getEndTime')
            ->willReturn(new \DateTimeImmutable('2025-01-15 10:00:00'));

        $transactionCallback = null;
        $this->entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->with($this->callback(function ($callback) use (&$transactionCallback): bool {
                $transactionCallback = $callback;
                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(TimeSlot::class, $slotId, LockMode::PESSIMISTIC_WRITE)
            ->willReturn($slot);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) use ($slotId, $patientEmail): bool {
                return $event instanceof SlotBookedEvent
                    && $event->slotId === $slotId->toString()
                    && $event->patientEmail === $patientEmail;
            }));

        $command = new BookSlotCommand($slotId, $patientEmail);
        $result = ($this->handler)($command);

        $this->assertInstanceOf(BookSlotResult::class, $result);
        $this->assertSame($slotId->toString(), $result->id);
        $this->assertTrue($result->isBooked);
        $this->assertEquals(new \DateTimeImmutable('2025-01-15 09:00:00'), $result->startTime);
        $this->assertEquals(new \DateTimeImmutable('2025-01-15 10:00:00'), $result->endTime);
    }

    public function testBookSlotThrowsConflictWhenAlreadyBooked(): void
    {
        $slotId = Uuid::v4();
        $patientEmail = 'patient@example.com';

        $slot = $this->createMock(TimeSlot::class);
        $slot->expects($this->once())
            ->method('isBooked')
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(TimeSlot::class, $slotId, LockMode::PESSIMISTIC_WRITE)
            ->willReturn($slot);

        $command = new BookSlotCommand($slotId, $patientEmail);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('This time slot is already booked.');

        ($this->handler)($command);
    }

    public function testBookSlotThrowsNotFoundWhenSlotDoesNotExist(): void
    {
        $slotId = Uuid::v4();
        $patientEmail = 'patient@example.com';

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(TimeSlot::class, $slotId, LockMode::PESSIMISTIC_WRITE)
            ->willReturn(null);

        $command = new BookSlotCommand($slotId, $patientEmail);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage(sprintf('Time slot "%s" was not found.', $slotId->toString()));

        ($this->handler)($command);
    }

    public function testTransactionRollbackOnSlotNotFound(): void
    {
        $slotId = Uuid::v4();
        $patientEmail = 'patient@example.com';

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(TimeSlot::class, $slotId, LockMode::PESSIMISTIC_WRITE)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage(sprintf('Time slot "%s" was not found.', $slotId->toString()));

        $this->entityManager->expects($this->never())
            ->method('wrapInTransaction');

        ($this->handler)($command = new BookSlotCommand($slotId, $patientEmail));
    }

    public function testTransactionRollbackOnConflict(): void
    {
        $slotId = Uuid::v4();
        $patientEmail = 'patient@example.com';

        $slot = $this->createMock(TimeSlot::class);
        $slot->expects($this->once())
            ->method('isBooked')
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(TimeSlot::class, $slotId, LockMode::PESSIMISTIC_WRITE)
            ->willReturn($slot);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('This time slot is already booked.');

        $this->entityManager->expects($this->never())
            ->method('wrapInTransaction');

        ($this->handler)($command = new BookSlotCommand($slotId, $patientEmail));
    }

    public function testSlotBookedEventDispatchedAfterSuccessfulBooking(): void
    {
        $slotId = Uuid::v4();
        $patientEmail = 'patient@example.com';

        $slot = $this->createMock(TimeSlot::class);
        $slot->expects($this->once())
            ->method('isBooked')
            ->willReturn(false);
        $slot->expects($this->once())
            ->method('setIsBooked')
            ->with(true)
            ->willReturnSelf();
        $slot->expects($this->once())
            ->method('setPatientEmail')
            ->with($patientEmail)
            ->willReturnSelf();
        $slot->expects($this->once())
            ->method('getId')
            ->willReturn($slotId);
        $slot->expects($this->once())
            ->method('getStartTime')
            ->willReturn(new \DateTimeImmutable('2025-01-15 09:00:00'));
        $slot->expects($this->once())
            ->method('getEndTime')
            ->willReturn(new \DateTimeImmutable('2025-01-15 10:00:00'));

        $transactionCallback = null;
        $this->entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->with($this->callback(function ($callback) use (&$transactionCallback): bool {
                $transactionCallback = $callback;
                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(TimeSlot::class, $slotId, LockMode::PESSIMISTIC_WRITE)
            ->willReturn($slot);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SlotBookedEvent::class));

        $command = new BookSlotCommand($slotId, $patientEmail);
        ($this->handler)($command);

        $this->assertNotNull($transactionCallback);
    }

    public function testTransactionCallbackExecutedInsideWrapInTransaction(): void
    {
        $slotId = Uuid::v4();
        $patientEmail = 'patient@example.com';

        $slot = $this->createMock(TimeSlot::class);
        $slot->expects($this->once())
            ->method('isBooked')
            ->willReturn(false);
        $slot->expects($this->once())
            ->method('setIsBooked')
            ->with(true)
            ->willReturnSelf();
        $slot->expects($this->once())
            ->method('setPatientEmail')
            ->with($patientEmail)
            ->willReturnSelf();
        $slot->expects($this->once())
            ->method('getId')
            ->willReturn($slotId);
        $slot->expects($this->once())
            ->method('getStartTime')
            ->willReturn(new \DateTimeImmutable('2025-01-15 09:00:00'));
        $slot->expects($this->once())
            ->method('getEndTime')
            ->willReturn(new \DateTimeImmutable('2025-01-15 10:00:00'));

        $this->entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->with($this->isCallable());

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(TimeSlot::class, $slotId, LockMode::PESSIMISTIC_WRITE)
            ->willReturn($slot);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SlotBookedEvent::class));

        $command = new BookSlotCommand($slotId, $patientEmail);
        ($this->handler)($command);
    }

    public function testResultContainsCorrectSlotInformation(): void
    {
        $slotId = Uuid::v4();
        $patientEmail = 'patient@example.com';

        $slot = $this->createMock(TimeSlot::class);
        $slot->expects($this->once())
            ->method('isBooked')
            ->willReturn(false);
        $slot->expects($this->once())
            ->method('setIsBooked')
            ->with(true)
            ->willReturnSelf();
        $slot->expects($this->once())
            ->method('setPatientEmail')
            ->with($patientEmail)
            ->willReturnSelf();
        $slot->expects($this->once())
            ->method('getId')
            ->willReturn($slotId);
        $startTime = new \DateTimeImmutable('2025-01-15 09:00:00');
        $endTime = new \DateTimeImmutable('2025-01-15 10:00:00');
        $slot->expects($this->once())
            ->method('getStartTime')
            ->willReturn($startTime);
        $slot->expects($this->once())
            ->method('getEndTime')
            ->willReturn($endTime);

        $transactionCallback = null;
        $this->entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->with($this->callback(function ($callback) use (&$transactionCallback): bool {
                $transactionCallback = $callback;
                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(TimeSlot::class, $slotId, LockMode::PESSIMISTIC_WRITE)
            ->willReturn($slot);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SlotBookedEvent::class));

        $command = new BookSlotCommand($slotId, $patientEmail);
        $result = ($this->handler)($command);

        $this->assertInstanceOf(BookSlotResult::class, $result);
        $this->assertSame($slotId->toString(), $result->id);
        $this->assertTrue($result->isBooked);
        $this->assertEquals($startTime, $result->startTime);
        $this->assertEquals($endTime, $result->endTime);
    }
}
