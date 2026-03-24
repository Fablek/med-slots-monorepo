<?php

declare(strict_types=1);

namespace App\Booking\Command;

use Symfony\Component\Messenger\Attribute\AsMessage;
use Symfony\Component\Uid\Uuid;

#[AsMessage]
final readonly class BookSlotCommand
{
    public function __construct(
        public Uuid $slotId,
        public string $patientEmail,
    ) {
    }
}
