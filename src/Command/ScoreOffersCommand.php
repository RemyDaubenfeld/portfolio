<?php

namespace App\Command;

use App\Repository\JobOfferRepository;
use App\Service\JobSearch\OfferScorer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:job-search:score-offers',
    description: 'Recalcule la note de pertinence des offres à étudier (ou de toutes avec --all)',
)]
class ScoreOffersCommand extends Command
{
    public function __construct(
        private OfferScorer $scorer,
        private JobOfferRepository $offerRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('all', null, InputOption::VALUE_NONE, 'Recalcule toutes les offres, y compris celles déjà traitées');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('all')) {
            $offers = $this->offerRepository->findAll();
            $this->scorer->scoreAll($offers);
        } else {
            $this->scorer->rescoreToReview();
            $offers = $this->offerRepository->findLatestToReview(PHP_INT_MAX);
        }

        $io->table(
            ['Note', 'Offre', 'Lieu', 'Détail'],
            array_map(fn ($o) => [
                $o->getRelevanceScore(),
                mb_strimwidth($o->getTitle(), 0, 55, '…'),
                mb_strimwidth((string) $o->getLocation(), 0, 25, '…'),
                implode(', ', array_map(fn ($d) => sprintf('%s %+d', $d['label'], $d['points']), $o->getRelevanceDetails())),
            ], $offers),
        );
        $io->success(sprintf('%d offre(s) notée(s).', count($offers)));

        return Command::SUCCESS;
    }
}
