<?php

declare(strict_types=1);

namespace App\Booking\Event;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SlotBookedEventHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SlotBookedEvent $event): void
    {
        $line = \sprintf(
            '[SlotBookedEvent] Simulated booking email | slot=%s | patient=%s',
            $event->slotId,
            $event->patientEmail,
        );
        error_log($line);

        $this->logger->info('Booking confirmation email (simulated send)', [
            'slotId' => $event->slotId,
            'patientEmail' => $event->patientEmail,
        ]);
    }
}
