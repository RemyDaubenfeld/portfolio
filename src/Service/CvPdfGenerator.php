<?php

namespace App\Service;

use App\Repository\EducationRepository;
use App\Repository\ExperienceRepository;
use App\Repository\InterestRepository;
use App\Repository\LanguageRepository;
use App\Repository\ProjectRepository;
use App\Repository\SkillCategoryRepository;
use App\Repository\UserRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Données du CV et génération du PDF : utilisé par la page CV et joint aux candidatures envoyées par email.
 */
class CvPdfGenerator
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly ExperienceRepository $experienceRepo,
        private readonly EducationRepository $educationRepo,
        private readonly LanguageRepository $languageRepo,
        private readonly SkillCategoryRepository $skillCatRepo,
        private readonly InterestRepository $interestRepo,
        private readonly ProjectRepository $projectRepo,
        private readonly Environment $twig,
    ) {
    }

    public function getData(): array
    {
        $user = $this->userRepo->findOneBy([]);

        $age = null;
        if ($user?->getBirthDate()) {
            $birthDate = \DateTime::createFromFormat('d/m/Y', $user->getBirthDate())
                ?: new \DateTime($user->getBirthDate());
            $age = (new \DateTime())->diff($birthDate)->y;
        }

        return [
            'user'        => $user,
            'age'         => $age,
            'experiences' => $this->experienceRepo->findAllOrdered(),
            'educations'  => $this->educationRepo->findAllOrdered(),
            'languages'   => $this->languageRepo->findAllOrdered(),
            'skill_cats'  => $this->skillCatRepo->findBy([], ['sortOrder' => 'ASC']),
            'interests'   => $this->interestRepo->findForCv(),
            'projects'    => $this->projectRepo->findForCv(),
        ];
    }

    /** Octets du PDF. */
    public function render(): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->twig->render('cv/pdf.html.twig', $this->getData()));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function filename(): string
    {
        $user = $this->userRepo->findOneBy([]);

        return 'CV_' . strtoupper($user?->getLastName() ?? 'CV') . '_' . ucfirst(strtolower($user?->getFirstName() ?? '')) . '.pdf';
    }
}
