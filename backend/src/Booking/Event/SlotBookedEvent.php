<?php

declare(strict_types=1);

namespace App\Booking\Event;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
final readonly class SlotBookedEvent
{
    public function __construct(
        public string $slotId,
        public string $patientEmail,
    ) {
    }
}
