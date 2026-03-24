<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Doctor;
use App\Entity\TimeSlot;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $tomorrow = (new \DateTimeImmutable('tomorrow'))->setTime(0, 0);

        $house = new Doctor('Gregory', 'House', 'Ginekologia');
        $this->addSlotsForDoctor($house, $tomorrow, [[9, 0, 9, 30], [10, 0, 10, 30], [14, 0, 14, 30]]);

        $cuddy = new Doctor('Lisa', 'Cuddy', 'Endokrynologia');
        $this->addSlotsForDoctor($cuddy, $tomorrow, [[8, 30, 9, 0], [11, 0, 11, 30]]);

        $wilson = new Doctor('James', 'Wilson', 'Onkologia');
        $this->addSlotsForDoctor($wilson, $tomorrow, [[9, 30, 10, 0], [15, 0, 15, 30]]);

        $foreman = new Doctor('Eric', 'Foreman', 'Neurologia');
        $this->addSlotsForDoctor($foreman, $tomorrow, [[12, 0, 12, 30], [16, 0, 16, 30]]);

        foreach ([$house, $cuddy, $wilson, $foreman] as $doctor) {
            $manager->persist($doctor);
        }

        $manager->flush();
    }

    /**
     * @param list<array{0: int, 1: int, 2: int, 3: int}> $ranges Hours/minutes tuples: startH, startM, endH, endM
     */
    private function addSlotsForDoctor(Doctor $doctor, \DateTimeImmutable $day, array $ranges): void
    {
        foreach ($ranges as [$startHour, $startMinute, $endHour, $endMinute]) {
            $start = $day->setTime($startHour, $startMinute);
            $end = $day->setTime($endHour, $endMinute);
            $doctor->addTimeSlot(new TimeSlot($start, $end));
        }
    }
}
