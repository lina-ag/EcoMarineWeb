<?php

namespace App\Tests\Controller;

use App\Entity\ActionNettoyage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ActionNettoyageControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<ActionNettoyage> */
    private EntityRepository $actionNettoyageRepository;
    private string $path = '/action/nettoyage/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->actionNettoyageRepository = $this->manager->getRepository(ActionNettoyage::class);

        foreach ($this->actionNettoyageRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ActionNettoyage index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'action_nettoyage[date_action]' => 'Testing',
            'action_nettoyage[lieu]' => 'Testing',
        ]);

        self::assertResponseRedirects('/action/nettoyage');

        self::assertSame(1, $this->actionNettoyageRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new ActionNettoyage();
        $fixture->setDateAction('My Title');
        $fixture->setLieu('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ActionNettoyage');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new ActionNettoyage();
        $fixture->setDateAction('Value');
        $fixture->setLieu('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'action_nettoyage[date_action]' => 'Something New',
            'action_nettoyage[lieu]' => 'Something New',
        ]);

        self::assertResponseRedirects('/action/nettoyage');

        $fixture = $this->actionNettoyageRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getDateAction());
        self::assertSame('Something New', $fixture[0]->getLieu());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new ActionNettoyage();
        $fixture->setDateAction('Value');
        $fixture->setLieu('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/action/nettoyage');
        self::assertSame(0, $this->actionNettoyageRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
