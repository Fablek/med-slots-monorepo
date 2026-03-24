<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Doctor;
use App\Entity\TimeSlot;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class DoctorTest extends TestCase
{
    public function testCreateDoctor(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');
        $this->assertInstanceOf(Doctor::class, $doctor);
        $this->assertNull($doctor->getId());
    }

    public function testGetFirstName(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');
        $this->assertSame('Jan', $doctor->getFirstName());
    }

    public function testSetFirstName(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');
        $doctor->setFirstName('Nowe Imię');
        $this->assertSame('Nowe Imię', $doctor->getFirstName());
    }

    public function testGetLastName(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');
        $this->assertSame('Kowalski', $doctor->getLastName());
    }

    public function testSetLastName(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');
        $doctor->setLastName('Nowe Nazwisko');
        $this->assertSame('Nowe Nazwisko', $doctor->getLastName());
    }

    public function testGetSpecialty(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');
        $this->assertSame('Kardiolog', $doctor->getSpecialty());
    }

    public function testSetSpecialty(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');
        $doctor->setSpecialty('Dermatolog');
        $this->assertSame('Dermatolog', $doctor->getSpecialty());
    }

    public function testGetTimeSlots(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');
        $this->assertInstanceOf(ArrayCollection::class, $doctor->getTimeSlots());
        $this->assertCount(0, $doctor->getTimeSlots());
    }

    public function testAddTimeSlot(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');
        $timeSlot = new TimeSlot(
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2025-01-15 09:00:00'),
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2025-01-15 10:00:00'),
        );

        $doctor->addTimeSlot($timeSlot);

        $this->assertCount(1, $doctor->getTimeSlots());
        $this->assertSame($timeSlot, $doctor->getTimeSlots()->first());
    }

    public function testAddTimeSlotSetsDoctor(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');
        $timeSlot = new TimeSlot(
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2025-01-15 09:00:00'),
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2025-01-15 10:00:00'),
        );

        $doctor->addTimeSlot($timeSlot);

        $this->assertSame($doctor, $timeSlot->getDoctor());
    }

    public function testDuplicateTimeSlotPrevention(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');
        $timeSlot = new TimeSlot(
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2025-01-15 09:00:00'),
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2025-01-15 10:00:00'),
        );

        $doctor->addTimeSlot($timeSlot);
        $doctor->addTimeSlot($timeSlot);

        $this->assertCount(1, $doctor->getTimeSlots());
    }

    public function testConstructorSetsAllFields(): void
    {
        $doctor = new Doctor('Anna', 'Nowak', 'Pulmonolog');

        $this->assertSame('Anna', $doctor->getFirstName());
        $this->assertSame('Nowak', $doctor->getLastName());
        $this->assertSame('Pulmonolog', $doctor->getSpecialty());
        $this->assertCount(0, $doctor->getTimeSlots());
    }

    public function testCanHandleMultipleTimeSlots(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');

        for ($i = 0; $i < 5; $i++) {
            $timeSlot = new TimeSlot(
                \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "2025-01-15 09:00:00"),
                \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "2025-01-15 10:00:00"),
            );
            $doctor->addTimeSlot($timeSlot);
        }

        $this->assertCount(5, $doctor->getTimeSlots());
    }

    public function testGetSetDoctorRelationship(): void
    {
        $doctor = new Doctor('Jan', 'Kowalski', 'Kardiolog');
        $timeSlot = new TimeSlot(
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2025-01-15 09:00:00'),
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2025-01-15 10:00:00'),
        );

        $timeSlot->setDoctor($doctor);

        $this->assertSame($doctor, $timeSlot->getDoctor());
        $this->assertCount(1, $doctor->getTimeSlots());
        $this->assertSame($timeSlot, $doctor->getTimeSlots()->first());
    }
}
