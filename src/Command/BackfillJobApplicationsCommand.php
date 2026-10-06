<?php

namespace App\Command;

use App\Entity\JobApplication;
use App\Entity\JobOffer;
use App\Enum\JobApplicationStatus;
use App\Enum\JobApplicationType;
use App\Enum\JobOfferStatus;
use App\Repository\JobOfferRepository;
use App\Service\JobSearch\CompanyMatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:job-search:backfill-applications',
    description: 'Crée les candidatures des offres déjà marquées postulées / relancées / refusées... avant l\'arrivée du suivi des candidatures',
)]
class BackfillJobApplicationsCommand extends Command
{
    /** Statut d'offre => statut de la candidature à créer */
    private const STATUS_MAP = [
        'applied' => JobApplicationStatus::Sent,
        'follow_up' => JobApplicationStatus::FollowedUp,
        'interview' => JobApplicationStatus::Interview,
        'rejected' => JobApplicationStatus::Rejected,
        'accepted' => JobApplicationStatus::OfferReceived,
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private JobOfferRepository $offerRepository,
        private CompanyMatcher $companyMatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les candidatures qui seraient créées sans rien enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $offers = $this->offerRepository->findWithoutApplication(array_map(
            fn (string $status) => JobOfferStatus::from($status),
            array_keys(self::STATUS_MAP),
        ));

        $rows = [];
        foreach ($offers as $offer) {
            // Date lue avant toute modification : le rattachement à l'entreprise met à jour updatedAt
            [$sentAt, $dateSource] = $this->guessSentAt($offer);
            $status = self::STATUS_MAP[$offer->getApplicationStatus()->value];

            $this->companyMatcher->link($offer);
            $application = (new JobApplication())
                ->setType(JobApplicationType::JobPosting)
                ->setOffer($offer)
                ->setCompany($offer->getLinkedCompany())
                ->setSentAt($sentAt)
                ->setStatus($status);

            if ($status === JobApplicationStatus::FollowedUp) {
                $application->setFollowUpCount(1)->setLastFollowedUpAt($sentAt);
            }

            $this->em->persist($application);
            $rows[] = [$offer->getId(), mb_strimwidth((string) $application, 0, 60, '…'), $status->label(), $sentAt->format('d/m/Y'), $dateSource];
        }

        if (!$rows) {
            $io->success('Aucune offre à rattraper : toutes les offres postulées ont déjà leur candidature.');

            return Command::SUCCESS;
        }

        $io->table(['Offre', 'Candidature', 'Statut', 'Envoyée le', 'Date tirée de'], $rows);

        if ($input->getOption('dry-run')) {
            $io->note(sprintf('[dry-run] %d candidature(s) seraient créées.', count($rows)));

            return Command::SUCCESS;
        }

        $this->em->flush();
        $io->success(sprintf('%d candidature(s) créée(s).', count($rows)));

        return Command::SUCCESS;
    }

    /**
     * Cherche la date d'envoi dans les notes ("Postulé le 28/09/26"), sinon prend la dernière modification de l'offre.
     *
     * @return array{\DateTimeImmutable, string}
     */
    private function guessSentAt(JobOffer $offer): array
    {
        if (preg_match('#postul[ée]e?\s+le\s+(\d{1,2})/(\d{1,2})/(\d{2}|\d{4})\b#iu', (string) $offer->getNotes(), $m)) {
            $year = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
            $date = \DateTimeImmutable::createFromFormat('!Y-n-j', sprintf('%s-%d-%d', $year, $m[2], $m[1]));
            if ($date) {
                return [$date, 'notes'];
            }
        }

        return [$offer->getUpdatedAt() ?? $offer->getCreatedAt(), 'date de modification'];
    }
}
