<?php

namespace App\Command;

use App\Entity\Company;
use App\Enum\CompanySource;
use App\Enum\CompanyStatus;
use App\Repository\CompanyRepository;
use App\Service\JobSearch\CompanyNameNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:job-search:import-companies',
    description: 'Importe la liste d\'entreprises IT depuis un CSV (doublons nom + ville ignorés)',
)]
class ImportCompaniesCommand extends Command
{
    private const EXPECTED_HEADER = ['Entreprise', 'Region', 'Ville', 'Type', 'Site Web', 'Adresse', 'Effectif', 'Remote', 'Technologies', 'Metiers', 'Details', 'Statut'];

    public function __construct(
        private EntityManagerInterface $em,
        private CompanyRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'Chemin du fichier CSV', 'entreprises_import.csv')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche le résultat sans rien enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');

        if (!is_readable($file) || !($handle = fopen($file, 'r'))) {
            $io->error("Fichier illisible : $file");

            return Command::FAILURE;
        }

        $header = fgetcsv($handle, escape: '');
        if (is_array($header)) {
            // Excel ajoute parfois un BOM UTF-8 en tête de fichier
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        }
        if ($header !== self::EXPECTED_HEADER) {
            $io->error('En-tête inattendu : ' . implode(', ', (array) $header));

            return Command::FAILURE;
        }

        $created = 0;
        $skipped = [];
        $seen = [];

        while (($row = fgetcsv($handle, escape: '')) !== false) {
            if ($row === [null]) {
                continue;
            }

            $data = array_map(
                fn (?string $value) => ($value === null || trim($value) === '' || trim($value) === 'NC') ? null : trim($value),
                array_combine(self::EXPECTED_HEADER, array_pad($row, count(self::EXPECTED_HEADER), null)),
            );

            if (!$data['Entreprise']) {
                continue;
            }

            $key = CompanyNameNormalizer::normalize($data['Entreprise']) . '|' . mb_strtolower((string) $data['Ville']);
            if (isset($seen[$key]) || $this->exists($data['Entreprise'], $data['Ville'])) {
                $skipped[] = sprintf('%s (%s)', $data['Entreprise'], $data['Ville'] ?? '?');
                continue;
            }
            $seen[$key] = true;

            $company = (new Company())
                ->setName($data['Entreprise'])
                ->setRegion($data['Region'])
                ->setCity($data['Ville'])
                ->setType($data['Type'])
                ->setWebsite($data['Site Web'])
                ->setAddress($data['Adresse'])
                ->setWorkforce($data['Effectif'])
                ->setRemote($data['Remote'])
                ->setTechnologies($data['Technologies'])
                ->setJobs($data['Metiers'])
                ->setDetails($data['Details'])
                ->setSource(CompanySource::ItList)
                ->setStatus($this->mapStatus($data['Statut']));

            $this->em->persist($company);
            $created++;
        }
        fclose($handle);

        if ($skipped) {
            $io->note(sprintf("%d entreprise(s) déjà présente(s), ignorée(s) :\n- %s", count($skipped), implode("\n- ", $skipped)));
        }

        if ($input->getOption('dry-run')) {
            $io->success("[dry-run] $created entreprise(s) seraient importées.");

            return Command::SUCCESS;
        }

        $this->em->flush();
        $io->success("$created entreprise(s) importée(s).");

        return Command::SUCCESS;
    }

    private function exists(string $name, ?string $city): bool
    {
        foreach ($this->repository->findByNormalizedName(CompanyNameNormalizer::normalize($name)) as $company) {
            if (mb_strtolower((string) $company->getCity()) === mb_strtolower((string) $city)) {
                return true;
            }
        }

        return false;
    }

    private function mapStatus(?string $status): CompanyStatus
    {
        return match (mb_strtolower((string) $status)) {
            'à qualifier', 'a qualifier' => CompanyStatus::ToQualify,
            'contactée', 'contactee' => CompanyStatus::Contacted,
            'sans réponse', 'sans reponse' => CompanyStatus::NoResponse,
            default => CompanyStatus::ToContact,
        };
    }
}
