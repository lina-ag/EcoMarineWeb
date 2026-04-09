<?php

namespace App\Tests\Controller;

use App\Entity\ActiviteEcologique;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ActiviteEcologiqueControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<ActiviteEcologique> */
    private EntityRepository $activiteEcologiqueRepository;
    private string $path = '/activite/ecologique/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->activiteEcologiqueRepository = $this->manager->getRepository(ActiviteEcologique::class);

        foreach ($this->activiteEcologiqueRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ActiviteEcologique index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'activite_ecologique[nom_activite]' => 'Testing',
            'activite_ecologique[date_activite]' => 'Testing',
            'activite_ecologique[capacite]' => 'Testing',
            'activite_ecologique[description]' => 'Testing',
        ]);

        self::assertResponseRedirects('/activite/ecologique');

        self::assertSame(1, $this->activiteEcologiqueRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new ActiviteEcologique();
        $fixture->setNomActivite('My Title');
        $fixture->setDateActivite('My Title');
        $fixture->setCapacite('My Title');
        $fixture->setDescription('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ActiviteEcologique');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new ActiviteEcologique();
        $fixture->setNomActivite('Value');
        $fixture->setDateActivite('Value');
        $fixture->setCapacite('Value');
        $fixture->setDescription('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'activite_ecologique[nom_activite]' => 'Something New',
            'activite_ecologique[date_activite]' => 'Something New',
            'activite_ecologique[capacite]' => 'Something New',
            'activite_ecologique[description]' => 'Something New',
        ]);

        self::assertResponseRedirects('/activite/ecologique');

        $fixture = $this->activiteEcologiqueRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getNomActivite());
        self::assertSame('Something New', $fixture[0]->getDateActivite());
        self::assertSame('Something New', $fixture[0]->getCapacite());
        self::assertSame('Something New', $fixture[0]->getDescription());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new ActiviteEcologique();
        $fixture->setNomActivite('Value');
        $fixture->setDateActivite('Value');
        $fixture->setCapacite('Value');
        $fixture->setDescription('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/activite/ecologique');
        self::assertSame(0, $this->activiteEcologiqueRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
