<?php

namespace App\Tests\Controller;

use App\Entity\PredictionEchouage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PredictionEchouageControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<PredictionEchouage> */
    private EntityRepository $predictionEchouageRepository;
    private string $path = '/prediction/echouage/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->predictionEchouageRepository = $this->manager->getRepository(PredictionEchouage::class);

        foreach ($this->predictionEchouageRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('PredictionEchouage index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'prediction_echouage[date_prediction]' => 'Testing',
            'prediction_echouage[zone]' => 'Testing',
            'prediction_echouage[niveau_risque]' => 'Testing',
            'prediction_echouage[espece_concernee]' => 'Testing',
            'prediction_echouage[temperature_eau]' => 'Testing',
            'prediction_echouage[conditions_meteo]' => 'Testing',
            'prediction_echouage[recommandations]' => 'Testing',
        ]);

        self::assertResponseRedirects('/prediction/echouage');

        self::assertSame(1, $this->predictionEchouageRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new PredictionEchouage();
        $fixture->setDatePrediction('My Title');
        $fixture->setZone('My Title');
        $fixture->setNiveauRisque('My Title');
        $fixture->setEspeceConcernee('My Title');
        $fixture->setTemperatureEau('My Title');
        $fixture->setConditionsMeteo('My Title');
        $fixture->setRecommandations('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('PredictionEchouage');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new PredictionEchouage();
        $fixture->setDatePrediction('Value');
        $fixture->setZone('Value');
        $fixture->setNiveauRisque('Value');
        $fixture->setEspeceConcernee('Value');
        $fixture->setTemperatureEau('Value');
        $fixture->setConditionsMeteo('Value');
        $fixture->setRecommandations('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'prediction_echouage[date_prediction]' => 'Something New',
            'prediction_echouage[zone]' => 'Something New',
            'prediction_echouage[niveau_risque]' => 'Something New',
            'prediction_echouage[espece_concernee]' => 'Something New',
            'prediction_echouage[temperature_eau]' => 'Something New',
            'prediction_echouage[conditions_meteo]' => 'Something New',
            'prediction_echouage[recommandations]' => 'Something New',
        ]);

        self::assertResponseRedirects('/prediction/echouage');

        $fixture = $this->predictionEchouageRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getDatePrediction());
        self::assertSame('Something New', $fixture[0]->getZone());
        self::assertSame('Something New', $fixture[0]->getNiveauRisque());
        self::assertSame('Something New', $fixture[0]->getEspeceConcernee());
        self::assertSame('Something New', $fixture[0]->getTemperatureEau());
        self::assertSame('Something New', $fixture[0]->getConditionsMeteo());
        self::assertSame('Something New', $fixture[0]->getRecommandations());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new PredictionEchouage();
        $fixture->setDatePrediction('Value');
        $fixture->setZone('Value');
        $fixture->setNiveauRisque('Value');
        $fixture->setEspeceConcernee('Value');
        $fixture->setTemperatureEau('Value');
        $fixture->setConditionsMeteo('Value');
        $fixture->setRecommandations('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/prediction/echouage');
        self::assertSame(0, $this->predictionEchouageRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
