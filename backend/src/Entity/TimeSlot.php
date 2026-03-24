<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Filter\TimeSlotDoctorIdFilter;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

#[ApiFilter(SearchFilter::class, properties: [
    'doctor.id' => 'exact',
    'isBooked' => 'exact',
])]
#[ApiFilter(TimeSlotDoctorIdFilter::class, properties: ['doctor_id' => 'exact'])]
#[ApiResource(
    shortName: 'Slot',
    operations: [
        new GetCollection(
            uriTemplate: '/slots',
            normalizationContext: ['groups' => ['slot:read']],
        ),
        new GetCollection(
            uriTemplate: '/doctors/{doctorId}/slots',
            uriVariables: [
                'doctorId' => new Link(fromClass: Doctor::class, toProperty: 'doctor', identifiers: ['id']),
            ],
            normalizationContext: ['groups' => ['slot:read']],
        ),
        new Get(
            uriTemplate: '/slots/{id}',
            normalizationContext: ['groups' => ['slot:read']],
        ),
    ],
    formats: [
        'jsonld' => ['application/ld+json'],
        'json' => ['application/json'],
    ],
)]
#[ORM\Entity]
#[ORM\Table(name: 'time_slots')]
class TimeSlot
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Doctor::class, inversedBy: 'timeSlots')]
    #[ORM\JoinColumn(name: 'doctor_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Doctor $doctor = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(options: ['default' => false])]
    private bool $isBooked = false;

    #[ORM\Column(length: 320, nullable: true)]
    private ?string $patientEmail = null;

    public function __construct(\DateTimeImmutable $startTime, \DateTimeImmutable $endTime, bool $isBooked = false, ?string $patientEmail = null)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->isBooked = $isBooked;
        $this->patientEmail = $patientEmail;
    }

    #[Groups(['slot:read'])]
    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getDoctor(): ?Doctor
    {
        return $this->doctor;
    }

    public function setDoctor(?Doctor $doctor): self
    {
        $this->doctor = $doctor;

        return $this;
    }

    #[Groups(['slot:read'])]
    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    #[Groups(['slot:read'])]
    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    #[Groups(['slot:read'])]
    #[SerializedName('isBooked')]
    public function isBooked(): bool
    {
        return $this->isBooked;
    }

    public function setIsBooked(bool $isBooked): self
    {
        $this->isBooked = $isBooked;

        return $this;
    }

    public function getPatientEmail(): ?string
    {
        return $this->patientEmail;
    }

    public function setPatientEmail(?string $patientEmail): self
    {
        $this->patientEmail = $patientEmail;

        return $this;
    }
}
