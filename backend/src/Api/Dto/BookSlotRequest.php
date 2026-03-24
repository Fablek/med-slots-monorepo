<?php

declare(strict_types=1);

namespace App\Api\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BookSlotRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'patientEmail is required.')]
        #[Assert\Email(message: 'patientEmail must be a valid email address.')]
        public string $patientEmail,
    ) {
    }
}
