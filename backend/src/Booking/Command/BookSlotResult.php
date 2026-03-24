<?php

declare(strict_types=1);

namespace App\Booking\Command;

final readonly class BookSlotResult
{
    public function __construct(
        public string $id,
        public bool $isBooked,
        public \DateTimeImmutable $startTime,
        public \DateTimeImmutable $endTime,
    ) {
    }
}
