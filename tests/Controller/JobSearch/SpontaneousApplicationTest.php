<?php

namespace App\Tests\Controller\JobSearch;

use App\Entity\AdminUser;
use App\Entity\Company;
use App\Entity\CoverLetterTemplate;
use App\Entity\JobApplication;
use App\Entity\User;
use App\Enum\CompanyStatus;
use App\Enum\JobApplicationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;

/**
 * Candidatures spontanées : préparation en lot depuis les entreprises, puis envoi par email.
 */
final class SpontaneousApplicationTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $connection = $this->em->getConnection();
        foreach (['job_application', 'job_offer', 'company', 'cover_letter_template', 'user', 'admin_user'] as $table) {
            $connection->executeStatement("DELETE FROM `$table`");
        }

        $admin = (new AdminUser())->setEmail('admin@test.local')->setPassword('unused');
        $this->em->persist($admin);
        $this->em->persist((new User())->setFirstName('Rémy')->setLastName('Daubenfeld')->setJobTitle('Développeur web')->setLocation('57160 Moulins-lès-Metz')->setEmail('profil@test.local'));
        $this->em->flush();
        $this->client->loginUser($admin, 'admin');
    }

    public function testPrepareApplicationsForSelectedCompanies(): void
    {
        $template = $this->createTemplate();
        $acme = $this->createCompany('ACME', 'jobs@acme.test');
        $globex = $this->createCompany('Globex', null);
        $busy = $this->createCompany('Initech', 'rh@initech.test');
        $this->em->persist((new JobApplication())->setCompany($busy)->setStatus(JobApplicationStatus::Sent));
        $this->em->flush();

        $crawler = $this->batch('/job-search/company', 'prepareApplications', '/job-search/company/prepare-applications', [$acme, $globex, $busy]);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Envoi par email à jobs@acme.test');
        self::assertSelectorTextContains('body', 'Pas d\'email de contact');
        self::assertSelectorTextContains('body', 'Candidature déjà en cours');

        $this->client->submit($crawler->selectButton('Créer 2 brouillons')->form(['template' => $template->getId()]));
        self::assertResponseRedirects();
        self::assertStringContainsString('status', urldecode($this->client->getResponse()->headers->get('Location')));

        $this->em->clear();
        $drafts = $this->em->getRepository(JobApplication::class)->findBy(['status' => JobApplicationStatus::Draft]);
        self::assertCount(2, $drafts);
        $letters = array_map(fn (JobApplication $a) => $a->getCoverLetter(), $drafts);
        self::assertContains("Je souhaite rejoindre ACME à Metz.\n\nCordialement.", $letters);
        self::assertSame(CompanyStatus::ToContact, $this->em->find(Company::class, $acme->getId())->getStatus());
    }

    public function testPrepareSingleCompanyOpensLetterEditor(): void
    {
        $template = $this->createTemplate();
        $company = $this->createCompany('ACME', null);

        $crawler = $this->client->request('GET', sprintf('/job-search/company/%d/prepare-application', $company->getId()));
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Créer le brouillon')->form(['template' => $template->getId()]));

        $application = $this->em->getRepository(JobApplication::class)->findOneBy([]);
        self::assertResponseRedirects(sprintf('/job-search/job-application/%d/cover-letter', $application->getId()));
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Pas d\'email de contact');
        self::assertSelectorNotExists('button:contains("Envoyer par email")');
    }

    public function testSendApplicationByEmail(): void
    {
        $application = $this->createDraft($this->createCompany('ACME', 'jobs@acme.test'));

        $this->client->request('GET', sprintf('/job-search/job-application/%d/email-preview', $application->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Candidature spontanée — Développeur web');

        $this->client->request('POST', sprintf('/job-search/job-application/%d/send-email', $application->getId()));
        self::assertResponseRedirects();

        self::assertEmailCount(1);
        /** @var Email $email */
        $email = self::getMailerMessage();
        self::assertEmailAddressContains($email, 'to', 'jobs@acme.test');
        self::assertEmailAddressContains($email, 'reply-to', 'profil@test.local');
        self::assertEmailSubjectContains($email, 'Candidature spontanée — Développeur web');
        self::assertEmailHtmlBodyContains($email, 'Premier paragraphe.');
        self::assertEmailTextBodyContains($email, 'Second paragraphe.');
        self::assertEmailAttachmentCount($email, 1);
        self::assertSame('CV_DAUBENFELD_Rémy.pdf', $email->getAttachments()[0]->getFilename());

        $this->em->clear();
        $application = $this->em->find(JobApplication::class, $application->getId());
        self::assertSame(JobApplicationStatus::Sent, $application->getStatus());
        self::assertNotNull($application->getSentAt());
        self::assertSame($application->getSentAt()->modify(JobApplication::FOLLOW_UP_DELAY)->format('Y-m-d'), $application->getFollowUpAt()?->format('Y-m-d'));
        self::assertSame(CompanyStatus::Contacted, $application->getCompany()->getStatus());
    }

    public function testBatchSendSkipsApplicationsNotReady(): void
    {
        $ready = $this->createDraft($this->createCompany('ACME', 'jobs@acme.test'));
        $noEmail = $this->createDraft($this->createCompany('Globex', null));
        $emptyLetter = $this->createDraft($this->createCompany('Initech', 'rh@initech.test'))->setCoverLetter(null);
        $this->em->flush();

        $this->batch('/job-search/job-application', 'sendEmails', '/job-search/job-application/send-emails', [$ready, $noEmail, $emptyLetter]);
        self::assertResponseRedirects();
        self::assertEmailCount(1);

        $this->client->followRedirect();
        self::assertSelectorTextContains('body', '1 candidature(s) envoyée(s)');
        self::assertSelectorTextContains('body', 'Globex (pas d\'email de contact)');
        self::assertSelectorTextContains('body', 'Initech (lettre vide)');

        $this->em->clear();
        self::assertSame(JobApplicationStatus::Sent, $this->em->find(JobApplication::class, $ready->getId())->getStatus());
        self::assertSame(JobApplicationStatus::Draft, $this->em->find(JobApplication::class, $noEmail->getId())->getStatus());
    }

    public function testMarkAsSentWithoutEmail(): void
    {
        $application = $this->createDraft($this->createCompany('ACME', null));

        $this->client->request('POST', sprintf('/job-search/job-application/%d/mark-as-sent', $application->getId()));
        self::assertResponseRedirects();
        self::assertEmailCount(0);

        $this->em->clear();
        $application = $this->em->find(JobApplication::class, $application->getId());
        self::assertSame(JobApplicationStatus::Sent, $application->getStatus());
        self::assertNotNull($application->getFollowUpAt());
        self::assertSame(CompanyStatus::Contacted, $application->getCompany()->getStatus());
    }

    public function testActionsRejectGet(): void
    {
        $application = $this->createDraft($this->createCompany('ACME', 'jobs@acme.test'));

        foreach (['send-email', 'mark-as-sent'] as $action) {
            $this->client->request('GET', sprintf('/job-search/job-application/%d/%s', $application->getId(), $action));
            self::assertResponseStatusCodeSame(405);
        }
        // En GET, ces URL retombent sur la route de détail (/{entityId}) : 404
        foreach (['/job-search/job-application/send-emails', '/job-search/company/create-applications', '/job-search/company/prepare-applications'] as $url) {
            $this->client->request('GET', $url);
            self::assertContains($this->client->getResponse()->getStatusCode(), [404, 405]);
        }
        self::assertEmailCount(0);
    }

    /** Coche des lignes dans la liste et déclenche la batch action, comme le fait le JS d'EasyAdmin. */
    private function batch(string $indexUrl, string $actionName, string $actionUrl, array $entities)
    {
        $crawler = $this->client->request('GET', $indexUrl);
        $button = $crawler->filter(sprintf('[data-action-name="%s"]', $actionName));
        self::assertCount(1, $button, sprintf('Batch action "%s" absente de la liste.', $actionName));

        return $this->client->request('POST', $actionUrl, [
            'batchActionName' => $actionName,
            'entityFqcn' => $button->attr('data-entity-fqcn'),
            'batchActionUrl' => $actionUrl,
            'batchActionCsrfToken' => $button->attr('data-action-csrf-token'),
            'batchActionEntityIds' => array_map(fn ($entity) => $entity->getId(), $entities),
        ]);
    }

    private function createTemplate(): CoverLetterTemplate
    {
        $template = (new CoverLetterTemplate())->setName('Spontanée')->setBody("Je souhaite rejoindre {entreprise} à {ville}.\n\nCordialement.");
        $this->em->persist($template);
        $this->em->flush();

        return $template;
    }

    private function createCompany(string $name, ?string $email): Company
    {
        $company = (new Company())->setName($name)->setCity('Metz')->setContactEmail($email)->setStatus(CompanyStatus::ToContact);
        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }

    private function createDraft(Company $company): JobApplication
    {
        $application = (new JobApplication())->setCompany($company)->setCoverLetter("Premier paragraphe.\r\n\r\nSecond paragraphe.");
        $this->em->persist($application);
        $this->em->flush();

        return $application;
    }
}
