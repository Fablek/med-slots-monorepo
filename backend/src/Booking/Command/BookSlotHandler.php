<?php

declare(strict_types=1);

namespace App\Booking\Command;

use App\Booking\Event\SlotBookedEvent;
use App\Entity\TimeSlot;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class BookSlotHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(BookSlotCommand $command): BookSlotResult
    {
        $result = null;

        $this->entityManager->wrapInTransaction(function () use ($command, &$result): void {
            $slot = $this->entityManager->find(
                TimeSlot::class,
                $command->slotId,
                LockMode::PESSIMISTIC_WRITE,
            );

            if (!$slot instanceof TimeSlot) {
                throw new NotFoundHttpException(\sprintf('Time slot "%s" was not found.', $command->slotId->toString()));
            }

            if ($slot->isBooked()) {
                throw new ConflictHttpException('This time slot is already booked.');
            }

            $slot->setIsBooked(true);
            $slot->setPatientEmail($command->patientEmail);

            $this->entityManager->flush();

            $id = $slot->getId();
            \assert(null !== $id);

            $result = new BookSlotResult(
                id: $id->toString(),
                isBooked: $slot->isBooked(),
                startTime: $slot->getStartTime(),
                endTime: $slot->getEndTime(),
            );
        });

        \assert($result instanceof BookSlotResult);

        $this->bus->dispatch(
            new SlotBookedEvent(
                slotId: $command->slotId->toString(),
                patientEmail: $command->patientEmail,
            ),
        );

        return $result;
    }
}
