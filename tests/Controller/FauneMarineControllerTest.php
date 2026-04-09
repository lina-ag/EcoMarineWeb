<?php

namespace App\Tests\Controller;

use App\Entity\FauneMarine;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FauneMarineControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<FauneMarine> */
    private EntityRepository $fauneMarineRepository;
    private string $path = '/faune/marine/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->fauneMarineRepository = $this->manager->getRepository(FauneMarine::class);

        foreach ($this->fauneMarineRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('FauneMarine index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'faune_marine[espece]' => 'Testing',
            'faune_marine[etat]' => 'Testing',
            'faune_marine[description]' => 'Testing',
        ]);

        self::assertResponseRedirects('/faune/marine');

        self::assertSame(1, $this->fauneMarineRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new FauneMarine();
        $fixture->setEspece('My Title');
        $fixture->setEtat('My Title');
        $fixture->setDescription('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('FauneMarine');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new FauneMarine();
        $fixture->setEspece('Value');
        $fixture->setEtat('Value');
        $fixture->setDescription('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'faune_marine[espece]' => 'Something New',
            'faune_marine[etat]' => 'Something New',
            'faune_marine[description]' => 'Something New',
        ]);

        self::assertResponseRedirects('/faune/marine');

        $fixture = $this->fauneMarineRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getEspece());
        self::assertSame('Something New', $fixture[0]->getEtat());
        self::assertSame('Something New', $fixture[0]->getDescription());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new FauneMarine();
        $fixture->setEspece('Value');
        $fixture->setEtat('Value');
        $fixture->setDescription('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/faune/marine');
        self::assertSame(0, $this->fauneMarineRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
