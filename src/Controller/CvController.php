<?php

namespace App\Controller;

use App\Repository\EducationRepository;
use App\Repository\ExperienceRepository;
use App\Repository\InterestRepository;
use App\Repository\LanguageRepository;
use App\Repository\ProjectRepository;
use App\Repository\SkillCategoryRepository;
use App\Repository\UserRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CvController extends AbstractController
{
    #[Route('/cv', name: 'app_cv')]
    public function index(
        UserRepository $userRepo,
        ExperienceRepository $experienceRepo,
        EducationRepository $educationRepo,
        LanguageRepository $languageRepo,
        SkillCategoryRepository $skillCatRepo,
        InterestRepository $interestRepo,
        ProjectRepository $projectRepo,
    ): Response {
        return $this->render(
            'cv/index.html.twig',
            $this->getData(
            $userRepo,
            $experienceRepo,
            $educationRepo,
            $languageRepo,
            $skillCatRepo,
            $interestRepo,
            $projectRepo,
            )
        );
    }

    #[Route('/cv/download', name: 'app_cv_download')]
    public function download(
        UserRepository $userRepo,
        ExperienceRepository $experienceRepo,
        EducationRepository $educationRepo,
        LanguageRepository $languageRepo,
        SkillCategoryRepository $skillCatRepo,
        InterestRepository $interestRepo,
        ProjectRepository $projectRepo,
    ): Response {
        $html = $this->renderView(
            'cv/pdf.html.twig',
            $this->getData(
                $userRepo,
                $experienceRepo,
                $educationRepo,
                $languageRepo,
                $skillCatRepo,
                $interestRepo,
                $projectRepo,

        #return $this->render('cv/pdf.html.twig', $this->getData(
        #$userRepo, $experienceRepo, $educationRepo, $languageRepo, $skillCatRepo, $interestRepo,
            )
        );
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $user = $userRepo->findOneBy([]);
        $filename = 'CV_' . strtoupper($user?->getLastName() ?? 'CV') . '_' . ucfirst(strtolower($user?->getFirstName() ?? '')) . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    private function getData(
    UserRepository $userRepo,
    ExperienceRepository $experienceRepo,
    EducationRepository $educationRepo,
    LanguageRepository $languageRepo,
    SkillCategoryRepository $skillCatRepo,
    InterestRepository $interestRepo,
    ProjectRepository $projectRepo,
): array {
    $user = $userRepo->findOneBy([]);

    $age = null;
if ($user?->getBirthDate()) {
    $birthDate = \DateTime::createFromFormat('d/m/Y', $user->getBirthDate())
        ?: new \DateTime($user->getBirthDate());
    $age = (new \DateTime())->diff($birthDate)->y;
}

    return [
        'user'        => $user,
        'age'         => $age,
        'experiences' => $experienceRepo->findAllOrdered(),
        'educations'  => $educationRepo->findAllOrdered(),
        'languages'   => $languageRepo->findAllOrdered(),
        'skill_cats'  => $skillCatRepo->findBy([], ['sortOrder' => 'ASC']),
        'interests'   => $interestRepo->findForCv(),
        'projects'    => $projectRepo->findForCv(),
    ];
}
}