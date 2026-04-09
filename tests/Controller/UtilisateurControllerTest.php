<?php

namespace App\Tests\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UtilisateurControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<Utilisateur> */
    private EntityRepository $utilisateurRepository;
    private string $path = '/utilisateur/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->utilisateurRepository = $this->manager->getRepository(Utilisateur::class);

        foreach ($this->utilisateurRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Utilisateur index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'utilisateur[nom]' => 'Testing',
            'utilisateur[prenom]' => 'Testing',
            'utilisateur[email]' => 'Testing',
            'utilisateur[mot_de_passe]' => 'Testing',
            'utilisateur[telephone]' => 'Testing',
            'utilisateur[role]' => 'Testing',
            'utilisateur[date_naissance]' => 'Testing',
            'utilisateur[face_encoding]' => 'Testing',
            'utilisateur[face_image]' => 'Testing',
            'utilisateur[created_at]' => 'Testing',
        ]);

        self::assertResponseRedirects('/utilisateur');

        self::assertSame(1, $this->utilisateurRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Utilisateur();
        $fixture->setNom('My Title');
        $fixture->setPrenom('My Title');
        $fixture->setEmail('My Title');
        $fixture->setMotDePasse('My Title');
        $fixture->setTelephone('My Title');
        $fixture->setRole('My Title');
        $fixture->setDateNaissance('My Title');
        $fixture->setFaceEncoding('My Title');
        $fixture->setFaceImage('My Title');
        $fixture->setCreatedAt('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Utilisateur');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Utilisateur();
        $fixture->setNom('Value');
        $fixture->setPrenom('Value');
        $fixture->setEmail('Value');
        $fixture->setMotDePasse('Value');
        $fixture->setTelephone('Value');
        $fixture->setRole('Value');
        $fixture->setDateNaissance('Value');
        $fixture->setFaceEncoding('Value');
        $fixture->setFaceImage('Value');
        $fixture->setCreatedAt('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'utilisateur[nom]' => 'Something New',
            'utilisateur[prenom]' => 'Something New',
            'utilisateur[email]' => 'Something New',
            'utilisateur[mot_de_passe]' => 'Something New',
            'utilisateur[telephone]' => 'Something New',
            'utilisateur[role]' => 'Something New',
            'utilisateur[date_naissance]' => 'Something New',
            'utilisateur[face_encoding]' => 'Something New',
            'utilisateur[face_image]' => 'Something New',
            'utilisateur[created_at]' => 'Something New',
        ]);

        self::assertResponseRedirects('/utilisateur');

        $fixture = $this->utilisateurRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getNom());
        self::assertSame('Something New', $fixture[0]->getPrenom());
        self::assertSame('Something New', $fixture[0]->getEmail());
        self::assertSame('Something New', $fixture[0]->getMotDePasse());
        self::assertSame('Something New', $fixture[0]->getTelephone());
        self::assertSame('Something New', $fixture[0]->getRole());
        self::assertSame('Something New', $fixture[0]->getDateNaissance());
        self::assertSame('Something New', $fixture[0]->getFaceEncoding());
        self::assertSame('Something New', $fixture[0]->getFaceImage());
        self::assertSame('Something New', $fixture[0]->getCreatedAt());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Utilisateur();
        $fixture->setNom('Value');
        $fixture->setPrenom('Value');
        $fixture->setEmail('Value');
        $fixture->setMotDePasse('Value');
        $fixture->setTelephone('Value');
        $fixture->setRole('Value');
        $fixture->setDateNaissance('Value');
        $fixture->setFaceEncoding('Value');
        $fixture->setFaceImage('Value');
        $fixture->setCreatedAt('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/utilisateur');
        self::assertSame(0, $this->utilisateurRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
