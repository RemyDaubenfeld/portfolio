<?php

namespace App\Command;

use App\Repository\JobOfferRepository;
use App\Service\JobSearch\CompanyMatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:job-search:link-offers',
    description: 'Rattache les offres existantes à une fiche entreprise (créée "à qualifier" si besoin)',
)]
class LinkJobOffersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobOfferRepository $offerRepository,
        private CompanyMatcher $companyMatcher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $offers = $this->offerRepository->findWithoutLinkedCompany();

        $linked = 0;
        foreach ($offers as $offer) {
            if ($this->companyMatcher->link($offer)) {
                $linked++;
            }
        }

        $this->em->flush();
        $io->success(sprintf('%d offre(s) rattachée(s) sur %d sans fiche entreprise.', $linked, count($offers)));

        return Command::SUCCESS;
    }
}
