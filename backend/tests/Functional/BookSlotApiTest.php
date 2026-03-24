<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Doctor;
use App\Entity\TimeSlot;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BookSlotApiTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testBookSlotReturnsJsonAndSecondBookingIs409(): void
    {
        $client = static::createClient();
        $this->resetSchema();

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $doctor = new Doctor('Greg', 'House', 'Diagnoza');
        $slot = new TimeSlot(
            new \DateTimeImmutable('+1 day 10:00'),
            new \DateTimeImmutable('+1 day 10:30'),
        );
        $slot->setDoctor($doctor);
        $em->persist($doctor);
        $em->persist($slot);
        $em->flush();

        $slotId = $slot->getId()?->toRfc4122();
        self::assertNotNull($slotId);

        $payload = json_encode(['patientEmail' => 'patient@example.com'], JSON_THROW_ON_ERROR);

        $client->request(
            'POST',
            '/api/slots/'.$slotId.'/book',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload,
        );

        self::assertResponseIsSuccessful();
        self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($data['isBooked']);
        self::assertSame($slotId, $data['id']);

        $client->request(
            'POST',
            '/api/slots/'.$slotId.'/book',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload,
        );

        self::assertResponseStatusCodeSame(409);
        self::assertStringContainsString('problem+json', (string) $client->getResponse()->headers->get('Content-Type'));
        $err = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(409, $err['status']);
    }

    public function testInvalidEmailReturns422ProblemJson(): void
    {
        $client = static::createClient();
        $this->resetSchema();

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $doctor = new Doctor('Greg', 'House', 'Diagnoza');
        $slot = new TimeSlot(
            new \DateTimeImmutable('+1 day 11:00'),
            new \DateTimeImmutable('+1 day 11:30'),
        );
        $slot->setDoctor($doctor);
        $em->persist($doctor);
        $em->persist($slot);
        $em->flush();

        $slotId = $slot->getId()?->toRfc4122();
        self::assertNotNull($slotId);

        $client->request(
            'POST',
            '/api/slots/'.$slotId.'/book',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['patientEmail' => 'not-an-email'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('problem+json', (string) $client->getResponse()->headers->get('Content-Type'));
    }

    private function resetSchema(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $tool = new SchemaTool($em);
        $meta = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($meta);
        $tool->createSchema($meta);
    }
}
