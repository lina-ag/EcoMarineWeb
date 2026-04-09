<?php

namespace App\Tests\Controller;

use App\Entity\Biodiversite;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BiodiversiteControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<Biodiversite> */
    private EntityRepository $biodiversiteRepository;
    private string $path = '/biodiversite/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->biodiversiteRepository = $this->manager->getRepository(Biodiversite::class);

        foreach ($this->biodiversiteRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Biodiversite index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'biodiversite[espece]' => 'Testing',
            'biodiversite[zone]' => 'Testing',
            'biodiversite[nombre]' => 'Testing',
            'biodiversite[date]' => 'Testing',
        ]);

        self::assertResponseRedirects('/biodiversite');

        self::assertSame(1, $this->biodiversiteRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Biodiversite();
        $fixture->setEspece('My Title');
        $fixture->setZone('My Title');
        $fixture->setNombre('My Title');
        $fixture->setDate('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Biodiversite');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Biodiversite();
        $fixture->setEspece('Value');
        $fixture->setZone('Value');
        $fixture->setNombre('Value');
        $fixture->setDate('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'biodiversite[espece]' => 'Something New',
            'biodiversite[zone]' => 'Something New',
            'biodiversite[nombre]' => 'Something New',
            'biodiversite[date]' => 'Something New',
        ]);

        self::assertResponseRedirects('/biodiversite');

        $fixture = $this->biodiversiteRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getEspece());
        self::assertSame('Something New', $fixture[0]->getZone());
        self::assertSame('Something New', $fixture[0]->getNombre());
        self::assertSame('Something New', $fixture[0]->getDate());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Biodiversite();
        $fixture->setEspece('Value');
        $fixture->setZone('Value');
        $fixture->setNombre('Value');
        $fixture->setDate('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/biodiversite');
        self::assertSame(0, $this->biodiversiteRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
