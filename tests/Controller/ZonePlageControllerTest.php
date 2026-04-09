<?php

namespace App\Tests\Controller;

use App\Entity\ZonePlage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ZonePlageControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<ZonePlage> */
    private EntityRepository $zonePlageRepository;
    private string $path = '/zone/plage/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->zonePlageRepository = $this->manager->getRepository(ZonePlage::class);

        foreach ($this->zonePlageRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ZonePlage index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'zone_plage[nom]' => 'Testing',
            'zone_plage[localisation]' => 'Testing',
            'zone_plage[statut]' => 'Testing',
        ]);

        self::assertResponseRedirects('/zone/plage');

        self::assertSame(1, $this->zonePlageRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new ZonePlage();
        $fixture->setNom('My Title');
        $fixture->setLocalisation('My Title');
        $fixture->setStatut('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ZonePlage');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new ZonePlage();
        $fixture->setNom('Value');
        $fixture->setLocalisation('Value');
        $fixture->setStatut('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'zone_plage[nom]' => 'Something New',
            'zone_plage[localisation]' => 'Something New',
            'zone_plage[statut]' => 'Something New',
        ]);

        self::assertResponseRedirects('/zone/plage');

        $fixture = $this->zonePlageRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getNom());
        self::assertSame('Something New', $fixture[0]->getLocalisation());
        self::assertSame('Something New', $fixture[0]->getStatut());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new ZonePlage();
        $fixture->setNom('Value');
        $fixture->setLocalisation('Value');
        $fixture->setStatut('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/zone/plage');
        self::assertSame(0, $this->zonePlageRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
