<?php

namespace App\Tests\Controller;

use App\Entity\Volontaire;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class VolontaireControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<Volontaire> */
    private EntityRepository $volontaireRepository;
    private string $path = '/volontaire/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->volontaireRepository = $this->manager->getRepository(Volontaire::class);

        foreach ($this->volontaireRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Volontaire index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'volontaire[nom]' => 'Testing',
            'volontaire[contact]' => 'Testing',
            'volontaire[id_action]' => 'Testing',
        ]);

        self::assertResponseRedirects('/volontaire');

        self::assertSame(1, $this->volontaireRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Volontaire();
        $fixture->setNom('My Title');
        $fixture->setContact('My Title');
        $fixture->setIdAction('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Volontaire');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Volontaire();
        $fixture->setNom('Value');
        $fixture->setContact('Value');
        $fixture->setIdAction('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'volontaire[nom]' => 'Something New',
            'volontaire[contact]' => 'Something New',
            'volontaire[id_action]' => 'Something New',
        ]);

        self::assertResponseRedirects('/volontaire');

        $fixture = $this->volontaireRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getNom());
        self::assertSame('Something New', $fixture[0]->getContact());
        self::assertSame('Something New', $fixture[0]->getIdAction());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Volontaire();
        $fixture->setNom('Value');
        $fixture->setContact('Value');
        $fixture->setIdAction('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/volontaire');
        self::assertSame(0, $this->volontaireRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
