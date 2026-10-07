<?php

namespace App\Service\JobSearch;

use App\Entity\CoverLetterTemplate;
use App\Entity\JobApplication;
use App\Entity\User;
use App\Repository\UserRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Remplit les modèles de lettre et génère le PDF (même principe que le CV).
 */
class CoverLetterGenerator
{
    private const MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Environment $twig,
    ) {
    }

    /** Remplace {entreprise}, {poste} et {ville} par les informations de la candidature. */
    public function fill(CoverLetterTemplate $template, JobApplication $application): string
    {
        return strtr($template->getBody(), [
            '{entreprise}' => $this->companyName($application),
            '{poste}' => $this->position($application),
            '{ville}' => $application->getCompany()?->getCity() ?? '',
        ]);
    }

    public function subject(JobApplication $application): string
    {
        return $application->getOffer()
            ? sprintf('Candidature au poste de %s', $this->position($application))
            : sprintf('Candidature spontanée — %s', $this->position($application));
    }

    public function renderPdf(JobApplication $application): string
    {
        $user = $this->userRepository->findOneBy([]);

        $html = $this->twig->render('cover_letter/pdf.html.twig', [
            'user' => $user,
            'senderCity' => $this->senderCity($user),
            'date' => $this->frenchDate(new \DateTimeImmutable()),
            'companyName' => $this->companyName($application),
            'company' => $application->getCompany(),
            'subject' => $this->subject($application),
            // Les textarea envoient des fins de ligne Windows : on les uniformise pour découper les paragraphes
            'body' => str_replace("\r\n", "\n", (string) $application->getCoverLetter()),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function filename(JobApplication $application): string
    {
        $user = $this->userRepository->findOneBy([]);
        $company = preg_replace('/[^A-Za-z0-9]+/', '_', CompanyNameNormalizer::normalize($this->companyName($application)));

        return sprintf('LM_%s_%s.pdf', strtoupper((string) $user?->getLastName()), trim($company, '_') ?: 'entreprise');
    }

    private function companyName(JobApplication $application): string
    {
        return $application->getCompany()?->getName() ?? $application->getOffer()?->getCompany() ?? '';
    }

    /** Intitulé de l'offre sans la mention "(H/F)", ou le titre du profil pour une candidature spontanée. */
    private function position(JobApplication $application): string
    {
        $title = $application->getOffer()?->getTitle() ?? $this->userRepository->findOneBy([])?->getJobTitle() ?? 'développeur web';

        return trim(preg_replace('/\(?\b[HF]\s*\/\s*[HF]\b\)?/u', '', $title), " \t-");
    }

    /** "57160 Moulins-lès-Metz" -> "Moulins-lès-Metz" */
    private function senderCity(?User $user): string
    {
        return trim(preg_replace('/^\d{5}\s*/', '', (string) $user?->getLocation()));
    }

    private function frenchDate(\DateTimeImmutable $date): string
    {
        return sprintf('%d %s %d', $date->format('j'), self::MONTHS[$date->format('n') - 1], $date->format('Y'));
    }
}
