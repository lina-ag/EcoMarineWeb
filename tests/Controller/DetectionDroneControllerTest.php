<?php

namespace App\Tests\Controller;

use App\Entity\DetectionDrone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DetectionDroneControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<DetectionDrone> */
    private EntityRepository $detectionDroneRepository;
    private string $path = '/detection/drone/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->detectionDroneRepository = $this->manager->getRepository(DetectionDrone::class);

        foreach ($this->detectionDroneRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('DetectionDrone index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'detection_drone[id_mission]' => 'Testing',
            'detection_drone[espece]' => 'Testing',
            'detection_drone[nombre_individus]' => 'Testing',
            'detection_drone[latitude]' => 'Testing',
            'detection_drone[longitude]' => 'Testing',
            'detection_drone[comportement]' => 'Testing',
            'detection_drone[confiance_ia]' => 'Testing',
            'detection_drone[image_path]' => 'Testing',
            'detection_drone[timestamp]' => 'Testing',
        ]);

        self::assertResponseRedirects('/detection/drone');

        self::assertSame(1, $this->detectionDroneRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new DetectionDrone();
        $fixture->setIdMission('My Title');
        $fixture->setEspece('My Title');
        $fixture->setNombreIndividus('My Title');
        $fixture->setLatitude('My Title');
        $fixture->setLongitude('My Title');
        $fixture->setComportement('My Title');
        $fixture->setConfianceIa('My Title');
        $fixture->setImagePath('My Title');
        $fixture->setTimestamp('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('DetectionDrone');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new DetectionDrone();
        $fixture->setIdMission('Value');
        $fixture->setEspece('Value');
        $fixture->setNombreIndividus('Value');
        $fixture->setLatitude('Value');
        $fixture->setLongitude('Value');
        $fixture->setComportement('Value');
        $fixture->setConfianceIa('Value');
        $fixture->setImagePath('Value');
        $fixture->setTimestamp('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'detection_drone[id_mission]' => 'Something New',
            'detection_drone[espece]' => 'Something New',
            'detection_drone[nombre_individus]' => 'Something New',
            'detection_drone[latitude]' => 'Something New',
            'detection_drone[longitude]' => 'Something New',
            'detection_drone[comportement]' => 'Something New',
            'detection_drone[confiance_ia]' => 'Something New',
            'detection_drone[image_path]' => 'Something New',
            'detection_drone[timestamp]' => 'Something New',
        ]);

        self::assertResponseRedirects('/detection/drone');

        $fixture = $this->detectionDroneRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getIdMission());
        self::assertSame('Something New', $fixture[0]->getEspece());
        self::assertSame('Something New', $fixture[0]->getNombreIndividus());
        self::assertSame('Something New', $fixture[0]->getLatitude());
        self::assertSame('Something New', $fixture[0]->getLongitude());
        self::assertSame('Something New', $fixture[0]->getComportement());
        self::assertSame('Something New', $fixture[0]->getConfianceIa());
        self::assertSame('Something New', $fixture[0]->getImagePath());
        self::assertSame('Something New', $fixture[0]->getTimestamp());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new DetectionDrone();
        $fixture->setIdMission('Value');
        $fixture->setEspece('Value');
        $fixture->setNombreIndividus('Value');
        $fixture->setLatitude('Value');
        $fixture->setLongitude('Value');
        $fixture->setComportement('Value');
        $fixture->setConfianceIa('Value');
        $fixture->setImagePath('Value');
        $fixture->setTimestamp('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/detection/drone');
        self::assertSame(0, $this->detectionDroneRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
