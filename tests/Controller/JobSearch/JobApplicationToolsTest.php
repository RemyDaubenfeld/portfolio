<?php

namespace App\Tests\Controller\JobSearch;

use App\Entity\AdminUser;
use App\Entity\Company;
use App\Entity\CoverLetterTemplate;
use App\Entity\JobApplication;
use App\Entity\JobOffer;
use App\Entity\Setting;
use App\Entity\User;
use App\Enum\JobApplicationEventType;
use App\Enum\JobApplicationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Historique des statuts, lettre de motivation et récapitulatif quotidien.
 */
final class JobApplicationToolsTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $connection = $this->em->getConnection();
        foreach (['job_application', 'job_offer', 'company', 'cover_letter_template', 'user', 'admin_user', 'setting'] as $table) {
            $connection->executeStatement("DELETE FROM `$table`");
        }

        $admin = (new AdminUser())->setEmail('admin@test.local')->setPassword('unused');
        $this->em->persist($admin);
        $this->em->persist((new User())->setFirstName('Rémy')->setLastName('Daubenfeld')->setLocation('57160 Moulins-lès-Metz'));
        $this->em->flush();
        $this->client->loginUser($admin, 'admin');
    }

    public function testHistoryRecordsCreationFollowUpAndStatusChanges(): void
    {
        $application = $this->createApplication(new \DateTimeImmutable('2026-09-28'));
        $this->assertEvents($application, [
            [JobApplicationEventType::Created, JobApplicationStatus::Sent, '2026-09-28'],
        ]);

        $application->followUp();
        $this->em->flush();
        $application->setStatus(JobApplicationStatus::Interview);
        $application->setStatus(JobApplicationStatus::Interview); // même statut : pas de nouvel événement
        $this->em->flush();

        $this->em->clear();
        $application = $this->em->find(JobApplication::class, $application->getId());
        $events = $application->getEvents()->toArray();
        self::assertCount(3, $events);
        self::assertSame(JobApplicationEventType::FollowedUp, $events[1]->getType());
        self::assertSame('Relance n°1', $events[1]->getLabel());
        self::assertSame(JobApplicationStatus::Interview, $events[2]->getStatus());

        $this->client->request('GET', sprintf('/job-search/job-application/%d', $application->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Historique');
        self::assertSelectorTextContains('body', 'Relance n°1');
    }

    public function testApplicationCreatedAtAdvancedStatusGetsTwoEvents(): void
    {
        $application = (new JobApplication())->setSentAt(new \DateTimeImmutable('2026-09-15'))->setStatus(JobApplicationStatus::Rejected);
        $this->em->persist($application);
        $this->em->flush();

        $this->assertEvents($application, [
            [JobApplicationEventType::Created, JobApplicationStatus::Sent, '2026-09-15'],
            [JobApplicationEventType::StatusChanged, JobApplicationStatus::Rejected, date('Y-m-d')],
        ]);
    }

    public function testCoverLetterFromTemplateToPdf(): void
    {
        $template = (new CoverLetterTemplate())->setName('ESN')->setBody("Je postule chez {entreprise} pour le poste de {poste}.\n\nÀ {ville}.");
        $this->em->persist($template);
        $application = $this->createApplication(new \DateTimeImmutable());

        $url = sprintf('/job-search/job-application/%d/cover-letter', $application->getId());
        $crawler = $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Objet : Candidature au poste de Développeur Symfony');

        $this->client->submit($crawler->selectButton('Appliquer')->form(['template' => $template->getId()]));
        self::assertResponseRedirects();
        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('textarea[name=body]', 'Je postule chez ACME pour le poste de Développeur Symfony.');
        self::assertSelectorTextContains('textarea[name=body]', 'À Metz.');

        $this->client->submit($crawler->selectButton('Enregistrer')->form(['body' => "Premier paragraphe.\r\n\r\nSecond paragraphe."]));
        $this->em->clear();
        self::assertSame("Premier paragraphe.\r\n\r\nSecond paragraphe.", $this->em->find(JobApplication::class, $application->getId())->getCoverLetter());

        $this->client->request('GET', $url . '.pdf?download=1');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertStringContainsString('attachment; filename=LM_DAUBENFELD_acme.pdf', (string) $this->client->getResponse()->headers->get('Content-Disposition'));
        self::assertStringStartsWith('%PDF', $this->client->getResponse()->getContent());
    }

    public function testDailyDigestIsSentOnlyWhenThereIsNews(): void
    {
        $tester = new CommandTester((new Application(self::$kernel))->find('app:job-search:daily-digest'));

        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
        self::assertEmailCount(0);
        self::assertStringContainsString('Rien de neuf', $tester->getDisplay());

        // Une relance due : le récapitulatif part
        $application = $this->createApplication(new \DateTimeImmutable('-12 days'));
        self::assertTrue($application->isFollowUpDue());

        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailAddressContains($email, 'to', 'admin@test.local');
        self::assertEmailSubjectContains($email, '1 relance à faire');
        self::assertEmailHtmlBodyContains($email, 'ACME — Développeur Symfony (H/F)');
    }

    public function testDailyDigestRecipientsCanBeConfigured(): void
    {
        $this->em->persist((new Setting())->setKey('job_search_digest_recipient')->setValue('moi@exemple.fr, autre@exemple.fr'));
        $this->createApplication(new \DateTimeImmutable('-12 days'));

        $tester = new CommandTester((new Application(self::$kernel))->find('app:job-search:daily-digest'));
        $tester->execute([]);

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailAddressContains($email, 'to', 'moi@exemple.fr');
        self::assertEmailAddressContains($email, 'to', 'autre@exemple.fr');
        self::assertEmailAddressNotContains($email, 'to', 'admin@test.local');
    }

    public function testDailyDigestApiRequiresKey(): void
    {
        $this->client->request('POST', '/api/job-search/daily-digest');
        self::assertResponseStatusCodeSame(401);

        $this->client->request('POST', '/api/job-search/daily-digest', server: ['HTTP_X_API_KEY' => $_ENV['JOB_OFFER_API_KEY'] ?? $_SERVER['JOB_OFFER_API_KEY']]);
        self::assertResponseIsSuccessful();
        self::assertSame('nothing_to_send', json_decode($this->client->getResponse()->getContent(), true)['status']);
    }

    private function createApplication(\DateTimeImmutable $sentAt): JobApplication
    {
        $company = (new Company())->setName('ACME')->setCity('Metz');
        $offer = (new JobOffer())->setTitle('Développeur Symfony (H/F)')->setCompany('ACME')->setSource('indeed')->setUrl('https://example.test/' . uniqid())->setLinkedCompany($company);
        $application = JobApplication::fromOffer($offer)->setSentAt($sentAt);

        $this->em->persist($company);
        $this->em->persist($offer);
        $this->em->persist($application);
        $this->em->flush();

        return $application;
    }

    /** @param array<array{JobApplicationEventType, JobApplicationStatus, string}> $expected */
    private function assertEvents(JobApplication $application, array $expected): void
    {
        $actual = array_map(
            fn ($e) => [$e->getType(), $e->getStatus(), $e->getOccurredAt()->format('Y-m-d')],
            $application->getEvents()->toArray(),
        );
        self::assertSame($expected, $actual);
    }
}
