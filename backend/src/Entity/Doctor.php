<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/doctors',
            normalizationContext: ['groups' => ['doctor:read']],
        ),
        new Get(
            uriTemplate: '/doctors/{id}',
            normalizationContext: ['groups' => ['doctor:read']],
        ),
    ],
    formats: [
        'jsonld' => ['application/ld+json'],
        'json' => ['application/json'],
    ],
)]
#[ORM\Entity]
#[ORM\Table(name: 'doctors')]
class Doctor
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $firstName;

    #[ORM\Column(length: 255)]
    private string $lastName;

    #[ORM\Column(length: 255)]
    private string $specialty;

    /** @var Collection<int, TimeSlot> */
    #[ORM\OneToMany(targetEntity: TimeSlot::class, mappedBy: 'doctor', cascade: ['persist'], orphanRemoval: true)]
    private Collection $timeSlots;

    public function __construct(string $firstName, string $lastName, string $specialty)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->specialty = $specialty;
        $this->timeSlots = new ArrayCollection();
    }

    #[Groups(['doctor:read'])]
    public function getId(): ?Uuid
    {
        return $this->id;
    }

    #[Groups(['doctor:read'])]
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    #[Groups(['doctor:read'])]
    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    #[Groups(['doctor:read'])]
    public function getSpecialty(): string
    {
        return $this->specialty;
    }

    public function setSpecialty(string $specialty): self
    {
        $this->specialty = $specialty;

        return $this;
    }

    /** @return Collection<int, TimeSlot> */
    public function getTimeSlots(): Collection
    {
        return $this->timeSlots;
    }

    public function addTimeSlot(TimeSlot $timeSlot): self
    {
        if (!$this->timeSlots->contains($timeSlot)) {
            $this->timeSlots->add($timeSlot);
            $timeSlot->setDoctor($this);
        }

        return $this;
    }
}
