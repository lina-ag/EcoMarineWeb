<?php

namespace App\Tests\Controller;

use App\Entity\MissionDrone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MissionDroneControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<MissionDrone> */
    private EntityRepository $missionDroneRepository;
    private string $path = '/mission/drone/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->missionDroneRepository = $this->manager->getRepository(MissionDrone::class);

        foreach ($this->missionDroneRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('MissionDrone index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'mission_drone[date_mission]' => 'Testing',
            'mission_drone[heure_debut]' => 'Testing',
            'mission_drone[heure_fin]' => 'Testing',
            'mission_drone[zone_survolee]' => 'Testing',
            'mission_drone[distance_parcourue]' => 'Testing',
            'mission_drone[altitude_vol]' => 'Testing',
            'mission_drone[conditions_vol]' => 'Testing',
            'mission_drone[observations]' => 'Testing',
        ]);

        self::assertResponseRedirects('/mission/drone');

        self::assertSame(1, $this->missionDroneRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new MissionDrone();
        $fixture->setDateMission('My Title');
        $fixture->setHeureDebut('My Title');
        $fixture->setHeureFin('My Title');
        $fixture->setZoneSurvolee('My Title');
        $fixture->setDistanceParcourue('My Title');
        $fixture->setAltitudeVol('My Title');
        $fixture->setConditionsVol('My Title');
        $fixture->setObservations('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('MissionDrone');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new MissionDrone();
        $fixture->setDateMission('Value');
        $fixture->setHeureDebut('Value');
        $fixture->setHeureFin('Value');
        $fixture->setZoneSurvolee('Value');
        $fixture->setDistanceParcourue('Value');
        $fixture->setAltitudeVol('Value');
        $fixture->setConditionsVol('Value');
        $fixture->setObservations('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'mission_drone[date_mission]' => 'Something New',
            'mission_drone[heure_debut]' => 'Something New',
            'mission_drone[heure_fin]' => 'Something New',
            'mission_drone[zone_survolee]' => 'Something New',
            'mission_drone[distance_parcourue]' => 'Something New',
            'mission_drone[altitude_vol]' => 'Something New',
            'mission_drone[conditions_vol]' => 'Something New',
            'mission_drone[observations]' => 'Something New',
        ]);

        self::assertResponseRedirects('/mission/drone');

        $fixture = $this->missionDroneRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getDateMission());
        self::assertSame('Something New', $fixture[0]->getHeureDebut());
        self::assertSame('Something New', $fixture[0]->getHeureFin());
        self::assertSame('Something New', $fixture[0]->getZoneSurvolee());
        self::assertSame('Something New', $fixture[0]->getDistanceParcourue());
        self::assertSame('Something New', $fixture[0]->getAltitudeVol());
        self::assertSame('Something New', $fixture[0]->getConditionsVol());
        self::assertSame('Something New', $fixture[0]->getObservations());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new MissionDrone();
        $fixture->setDateMission('Value');
        $fixture->setHeureDebut('Value');
        $fixture->setHeureFin('Value');
        $fixture->setZoneSurvolee('Value');
        $fixture->setDistanceParcourue('Value');
        $fixture->setAltitudeVol('Value');
        $fixture->setConditionsVol('Value');
        $fixture->setObservations('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/mission/drone');
        self::assertSame(0, $this->missionDroneRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
