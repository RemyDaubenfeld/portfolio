<?php

namespace App\Command;

use App\Service\JobSearch\DailyDigest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:job-search:daily-digest',
    description: 'Envoie le récapitulatif quotidien (relances dues, nouvelles offres) par email à l\'admin',
)]
class DailyDigestCommand extends Command
{
    public function __construct(private DailyDigest $digest)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Envoie même s\'il n\'y a ni relance due ni nouvelle offre')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche le contenu sans envoyer l\'email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $digest = $this->digest->collect(new \DateTimeImmutable('-24 hours'));

        $io->definitionList(
            ['Relances à faire' => count($digest['followUpsDue'])],
            ['Nouvelles offres (24 h)' => count($digest['newOffers'])],
            ['Relances des 3 prochains jours' => count($digest['upcomingFollowUps'])],
            ['Entretiens en cours' => count($digest['interviews'])],
        );

        if (!$this->digest->hasNews($digest) && !$input->getOption('force')) {
            $io->success('Rien de neuf : aucun email envoyé.');

            return Command::SUCCESS;
        }

        if ($input->getOption('dry-run')) {
            $io->note('[dry-run] Email non envoyé.');

            return Command::SUCCESS;
        }

        $io->success(sprintf('Récapitulatif envoyé à %s.', $this->digest->send($digest)));

        return Command::SUCCESS;
    }
}
