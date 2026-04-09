<?php

namespace App\Tests\Controller;

use App\Entity\Zonep;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ZonepControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<Zonep> */
    private EntityRepository $zonepRepository;
    private string $path = '/zonep/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->zonepRepository = $this->manager->getRepository(Zonep::class);

        foreach ($this->zonepRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Zonep index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'zonep[nomZone]' => 'Testing',
            'zonep[categorieZone]' => 'Testing',
            'zonep[status]' => 'Testing',
        ]);

        self::assertResponseRedirects('/zonep');

        self::assertSame(1, $this->zonepRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Zonep();
        $fixture->setNomZone('My Title');
        $fixture->setCategorieZone('My Title');
        $fixture->setStatus('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Zonep');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Zonep();
        $fixture->setNomZone('Value');
        $fixture->setCategorieZone('Value');
        $fixture->setStatus('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'zonep[nomZone]' => 'Something New',
            'zonep[categorieZone]' => 'Something New',
            'zonep[status]' => 'Something New',
        ]);

        self::assertResponseRedirects('/zonep');

        $fixture = $this->zonepRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getNomZone());
        self::assertSame('Something New', $fixture[0]->getCategorieZone());
        self::assertSame('Something New', $fixture[0]->getStatus());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Zonep();
        $fixture->setNomZone('Value');
        $fixture->setCategorieZone('Value');
        $fixture->setStatus('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/zonep');
        self::assertSame(0, $this->zonepRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
