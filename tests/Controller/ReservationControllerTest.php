<?php

namespace App\Tests\Controller;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReservationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<Reservation> */
    private EntityRepository $reservationRepository;
    private string $path = '/reservation/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->reservationRepository = $this->manager->getRepository(Reservation::class);

        foreach ($this->reservationRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Reservation index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'reservation[nom]' => 'Testing',
            'reservation[date_reservation]' => 'Testing',
            'reservation[email]' => 'Testing',
            'reservation[nombre_personnes]' => 'Testing',
            'reservation[activiteEcologique]' => 'Testing',
        ]);

        self::assertResponseRedirects('/reservation');

        self::assertSame(1, $this->reservationRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Reservation();
        $fixture->setNom('My Title');
        $fixture->setDateReservation('My Title');
        $fixture->setEmail('My Title');
        $fixture->setNombrePersonnes('My Title');
        $fixture->setActiviteEcologique('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Reservation');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Reservation();
        $fixture->setNom('Value');
        $fixture->setDateReservation('Value');
        $fixture->setEmail('Value');
        $fixture->setNombrePersonnes('Value');
        $fixture->setActiviteEcologique('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'reservation[nom]' => 'Something New',
            'reservation[date_reservation]' => 'Something New',
            'reservation[email]' => 'Something New',
            'reservation[nombre_personnes]' => 'Something New',
            'reservation[activiteEcologique]' => 'Something New',
        ]);

        self::assertResponseRedirects('/reservation');

        $fixture = $this->reservationRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getNom());
        self::assertSame('Something New', $fixture[0]->getDateReservation());
        self::assertSame('Something New', $fixture[0]->getEmail());
        self::assertSame('Something New', $fixture[0]->getNombrePersonnes());
        self::assertSame('Something New', $fixture[0]->getActiviteEcologique());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Reservation();
        $fixture->setNom('Value');
        $fixture->setDateReservation('Value');
        $fixture->setEmail('Value');
        $fixture->setNombrePersonnes('Value');
        $fixture->setActiviteEcologique('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/reservation');
        self::assertSame(0, $this->reservationRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
