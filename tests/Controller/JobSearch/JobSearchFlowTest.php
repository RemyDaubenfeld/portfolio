<?php

namespace App\Tests\Controller\JobSearch;

use App\Entity\AdminUser;
use App\Entity\Company;
use App\Entity\Department;
use App\Entity\JobApplication;
use App\Entity\JobOffer;
use App\Enum\CompanySource;
use App\Enum\CompanyStatus;
use App\Enum\JobApplicationStatus;
use App\Enum\JobOfferStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Parcours complet : une offre arrive, on postule depuis le tableau de bord, puis on relance.
 * Nécessite une base de test (DATABASE_URL avec le suffixe _test) à jour des migrations.
 */
final class JobSearchFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $connection = $this->em->getConnection();
        foreach (['job_application', 'job_offer', 'company', 'admin_user'] as $table) {
            $connection->executeStatement("DELETE FROM $table");
        }

        $admin = (new AdminUser())->setEmail('admin@test.local')->setPassword('unused');
        $this->em->persist($admin);
        $this->em->flush();
        $this->client->loginUser($admin, 'admin');
    }

    public function testApplyFromDashboardLinksCompanyAndSchedulesFollowUp(): void
    {
        // Entreprise issue de la liste IT, avec deux agences
        $this->em->persist((new Company())->setName('Abylsen EST')->setCity('Strasbourg')->setSource(CompanySource::ItList));
        $metz = (new Company())->setName('Abylsen EST')->setCity('Metz')->setRegion('Lorraine')->setSource(CompanySource::ItList);
        $this->em->persist($metz);
        $this->em->flush();

        $offer = $this->postOffer('Développeur Symfony', 'ABYLSEN EST SAS', '57 - METZ');
        self::assertSame($metz->getId(), $offer->getLinkedCompany()?->getId(), 'L\'offre doit être rattachée à l\'agence de Metz');

        $crawler = $this->client->request('GET', '/job-search');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Développeur Symfony');

        // Les régions mènent à la liste des entreprises filtrée (seule l'agence de Metz est en Lorraine)
        $this->client->click($crawler->filter('a.js-region')->link());
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->client->getCrawler()->filter('table.datagrid tbody tr[data-id]'));
        $crawler = $this->client->request('GET', '/job-search');

        // Le lien sous les offres ouvre la liste filtrée sur "À étudier"
        $this->client->click($crawler->filter('a.js-more')->link());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table.datagrid', 'Développeur Symfony');
        $crawler = $this->client->request('GET', '/job-search');

        $this->client->submit($crawler->filter(sprintf('form[action*="/job-offer/%d/apply"]', $offer->getId()))->form());
        self::assertResponseRedirects();

        $this->em->clear();
        $application = $this->em->getRepository(JobApplication::class)->findOneBy([]);
        self::assertNotNull($application);
        self::assertSame(JobApplicationStatus::Sent, $application->getStatus());
        self::assertSame(JobOfferStatus::Applied, $application->getOffer()->getApplicationStatus());
        self::assertSame(CompanyStatus::Contacted, $application->getCompany()->getStatus());
        self::assertEquals($application->getSentAt()->modify(JobApplication::FOLLOW_UP_DELAY), $application->getFollowUpAt());

        // Relance due : on avance artificiellement la date
        $application->setFollowUpAt(new \DateTimeImmutable('-1 day'));
        $this->em->flush();

        $crawler = $this->client->request('GET', '/job-search');
        $this->client->submit($crawler->filter(sprintf('form[action*="/job-application/%d/follow-up"]', $application->getId()))->form());
        self::assertResponseRedirects();

        $this->em->clear();
        $application = $this->em->getRepository(JobApplication::class)->find($application->getId());
        self::assertSame(JobApplicationStatus::FollowedUp, $application->getStatus());
        self::assertSame(1, $application->getFollowUpCount());
        self::assertSame(JobOfferStatus::FollowUp, $application->getOffer()->getApplicationStatus());
        self::assertGreaterThan(new \DateTimeImmutable(), $application->getFollowUpAt());
    }

    public function testUnknownCompanyIsCreatedToQualify(): void
    {
        $offer = $this->postOffer('Dev PHP', 'Nouvelle Boîte SARL', 'Strasbourg (67)');

        $company = $offer->getLinkedCompany();
        self::assertNotNull($company);
        self::assertSame(CompanySource::AutoOffer, $company->getSource());
        self::assertSame(CompanyStatus::ToQualify, $company->getStatus());
        self::assertSame('Strasbourg', $company->getCity());
        self::assertSame('Alsace', $company->getRegion());

        // Une deuxième offre de la même entreprise ne crée pas de doublon
        $this->postOffer('Dev Symfony', 'nouvelle boite', 'Strasbourg (67)');
        self::assertSame(1, $this->em->getRepository(Company::class)->count([]));
    }

    public function testCrudPagesRender(): void
    {
        $offer = $this->postOffer('Dev', 'ACME', 'Metz (57)');
        $application = JobApplication::fromOffer($offer);
        $this->em->persist($application);
        $this->em->flush();

        $ids = ['job-offer' => $offer->getId(), 'job-application' => $application->getId(), 'company' => $offer->getLinkedCompany()->getId()];
        foreach ($ids as $crud => $id) {
            foreach (["/job-search/$crud", "/job-search/$crud/new", "/job-search/$crud/$id", "/job-search/$crud/$id/edit"] as $url) {
                $this->client->request('GET', $url);
                self::assertResponseIsSuccessful($url);
            }
        }

        // Interface EasyAdmin en français (locale par défaut fr)
        $this->client->request('GET', '/job-search/job-offer');
        self::assertSelectorTextContains('body', 'Filtres');
        self::assertSelectorTextContains('body', 'Modifier');
    }

    public function testBackfillCreatesApplicationsForAlreadyAppliedOffers(): void
    {
        $applied = $this->postOffer('Dev PHP', 'ACME', 'Metz (57)');
        $rejected = $this->postOffer('Dev Symfony', 'Globex', 'Nancy (54)');
        $ignored = $this->postOffer('Dev Java', 'Initech', 'Metz (57)');

        $this->em->find(JobOffer::class, $applied->getId())->setApplicationStatus(JobOfferStatus::Applied)->setNotes('Postulé le 28/09/26 sur leur site');
        $this->em->find(JobOffer::class, $rejected->getId())->setApplicationStatus(JobOfferStatus::Rejected)->setNotes("Postulé le 15/09/2026\nRefusé le 20/09/26");
        $this->em->find(JobOffer::class, $ignored->getId())->setApplicationStatus(JobOfferStatus::NotInterested);
        $this->em->flush();

        $tester = new CommandTester((new Application(self::$kernel))->find('app:job-search:backfill-applications'));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $this->em->clear();
        $repository = $this->em->getRepository(JobApplication::class);
        self::assertSame(2, $repository->count([]));

        $application = $repository->findOneBy(['offer' => $applied->getId()]);
        self::assertSame(JobApplicationStatus::Sent, $application->getStatus());
        self::assertSame('2026-09-28', $application->getSentAt()->format('Y-m-d'));
        self::assertSame('2026-10-08', $application->getFollowUpAt()->format('Y-m-d'));
        self::assertSame(CompanyStatus::Contacted, $application->getCompany()->getStatus());

        $application = $repository->findOneBy(['offer' => $rejected->getId()]);
        self::assertSame(JobApplicationStatus::Rejected, $application->getStatus());
        self::assertSame('2026-09-15', $application->getSentAt()->format('Y-m-d'));
        self::assertNull($application->getFollowUpAt());

        // Relancer la commande ne crée pas de doublon
        $tester->execute([]);
        self::assertSame(2, $repository->count([]));
    }

    public function testSearchConfigExposesActiveDepartments(): void
    {
        $this->em->getConnection()->executeStatement('DELETE FROM department');
        $this->em->persist((new Department())->setCode('57')->setName('Moselle'));
        $this->em->persist((new Department())->setCode('54')->setName('Meurthe-et-Moselle'));
        $this->em->persist((new Department())->setCode('67')->setName('Bas-Rhin')->setActive(false));
        $this->em->flush();

        $this->client->request('GET', '/job-search/department');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/search-config', server: ['HTTP_X_API_KEY' => $_ENV['JOB_OFFER_API_KEY'] ?? $_SERVER['JOB_OFFER_API_KEY']]);
        self::assertResponseIsSuccessful();
        self::assertSame(['54', '57'], json_decode($this->client->getResponse()->getContent(), true)['departements']);
    }

    public function testAddingNegativeKeywordInAdminRescoresOffers(): void
    {
        $this->em->getConnection()->executeStatement('DELETE FROM negative_keyword');
        $offer = $this->postOffer('Développeur WinDev', 'ACME', 'Metz (57)');
        $before = $offer->getRelevanceScore();

        $crawler = $this->client->request('GET', '/job-search/negative-keyword/new');
        $this->client->submit($crawler->selectButton('Créer')->form(['NegativeKeyword[keyWord]' => 'WinDev', 'NegativeKeyword[active]' => true]));
        self::assertResponseRedirects();

        $this->em->clear();
        self::assertSame($before - 30, $this->em->find(JobOffer::class, $offer->getId())->getRelevanceScore());
    }

    public function testActionsRejectGet(): void
    {
        $offer = $this->postOffer('Dev', 'ACME', 'Metz (57)');
        $this->client->request('GET', sprintf('/job-search/job-offer/%d/apply', $offer->getId()));

        self::assertResponseStatusCodeSame(405);
    }

    private function postOffer(string $title, string $company, string $location): JobOffer
    {
        $this->client->request('POST', '/api/job-offers', server: [
            'HTTP_X_API_KEY' => $_ENV['JOB_OFFER_API_KEY'] ?? $_SERVER['JOB_OFFER_API_KEY'],
            'CONTENT_TYPE' => 'application/json',
        ], content: json_encode([
            'title' => $title,
            'company' => $company,
            'location' => $location,
            'source' => 'france_travail',
            'url' => 'https://example.test/' . uniqid(),
        ]));
        self::assertResponseStatusCodeSame(201);

        $this->em->clear();

        return $this->em->getRepository(JobOffer::class)->find(json_decode($this->client->getResponse()->getContent(), true)['id']);
    }
}
