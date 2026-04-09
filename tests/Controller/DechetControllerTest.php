<?php

namespace App\Tests\Controller;

use App\Entity\Dechet;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DechetControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<Dechet> */
    private EntityRepository $dechetRepository;
    private string $path = '/dechet/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->dechetRepository = $this->manager->getRepository(Dechet::class);

        foreach ($this->dechetRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Dechet index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'dechet[type]' => 'Testing',
            'dechet[quantite]' => 'Testing',
            'dechet[zone]' => 'Testing',
        ]);

        self::assertResponseRedirects('/dechet');

        self::assertSame(1, $this->dechetRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Dechet();
        $fixture->setType('My Title');
        $fixture->setQuantite('My Title');
        $fixture->setZone('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Dechet');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Dechet();
        $fixture->setType('Value');
        $fixture->setQuantite('Value');
        $fixture->setZone('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'dechet[type]' => 'Something New',
            'dechet[quantite]' => 'Something New',
            'dechet[zone]' => 'Something New',
        ]);

        self::assertResponseRedirects('/dechet');

        $fixture = $this->dechetRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getType());
        self::assertSame('Something New', $fixture[0]->getQuantite());
        self::assertSame('Something New', $fixture[0]->getZone());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Dechet();
        $fixture->setType('Value');
        $fixture->setQuantite('Value');
        $fixture->setZone('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/dechet');
        self::assertSame(0, $this->dechetRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
