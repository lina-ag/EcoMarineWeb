<?php

namespace App\Tests\Controller;

use App\Entity\Survzone;
use App\Entity\Zonep;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SurvzoneControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<Survzone> */
    private EntityRepository $survzoneRepository;
    private string $path = '/survzone/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->survzoneRepository = $this->manager->getRepository(Survzone::class);

        foreach ($this->survzoneRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Survzone index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'survzone[dateSurv]' => 'Testing',
            'survzone[observation]' => 'Testing',
            'survzone[idZone]' => 'Testing',
        ]);

        self::assertResponseRedirects('/survzone');

        self::assertSame(1, $this->survzoneRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $zonep = new Zonep();
        $zonep->setNomZone('Test Zone');
        $zonep->setCategorieZone('Test Category');
        $this->manager->persist($zonep);

        $fixture = new Survzone();
        $fixture->setDateSurv(new \DateTime('2023-01-01'));
        $fixture->setObservation('My Title');
        $fixture->setZonep($zonep);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getIdSurv()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Survzone');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $zonep = new Zonep();
        $zonep->setNomZone('Test Zone');
        $zonep->setCategorieZone('Test Category');
        $this->manager->persist($zonep);

        $fixture = new Survzone();
        $fixture->setDateSurv(new \DateTime('2023-01-01'));
        $fixture->setObservation('Value');
        $fixture->setZonep($zonep);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getIdSurv()));

        $this->client->submitForm('Update', [
            'survzone[dateSurv]' => '2023-02-01',
            'survzone[observation]' => 'Something New',
            'survzone[zonep]' => $zonep->getIdZone(),
        ]);

        self::assertResponseRedirects('/survzone');

        $fixture = $this->survzoneRepository->findAll();

        self::assertSame('2023-02-01', $fixture[0]->getDateSurv()->format('Y-m-d'));
        self::assertSame('Something New', $fixture[0]->getObservation());
        self::assertSame($zonep->getIdZone(), $fixture[0]->getZonep()->getIdZone());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $zonep = new Zonep();
        $zonep->setNomZone('Test Zone');
        $zonep->setCategorieZone('Test Category');
        $this->manager->persist($zonep);

        $fixture = new Survzone();
        $fixture->setDateSurv(new \DateTime('2023-01-01'));
        $fixture->setObservation('Value');
        $fixture->setZonep($zonep);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/survzone');
        self::assertSame(0, $this->survzoneRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
