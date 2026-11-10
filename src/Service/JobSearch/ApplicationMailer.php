<?php

namespace App\Service\JobSearch;

use App\Entity\JobApplication;
use App\Repository\UserRepository;
use App\Service\CvPdfGenerator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Twig\Environment;

/**
 * Envoi d'une candidature par email : la lettre dans le corps du message, le CV en pièce jointe.
 */
class ApplicationMailer
{
    public const HTML_TEMPLATE = 'emails/application.html.twig';
    public const TEXT_TEMPLATE = 'emails/application.txt.twig';

    public function __construct(
        private readonly CoverLetterGenerator $coverLetterGenerator,
        private readonly CvPdfGenerator $cvPdfGenerator,
        private readonly UserRepository $userRepository,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        #[Autowire('%env(MAILER_FROM)%')] private readonly string $mailerFrom,
    ) {
    }

    /**
     * Envoie le mail puis passe la candidature en "envoyée" (le flush reste à la charge de l'appelant).
     * En cas d'échec d'envoi, l'exception remonte et la candidature reste en brouillon.
     */
    public function send(JobApplication $application): void
    {
        if (!$application->canBeEmailed()) {
            throw new \LogicException(sprintf('La candidature %s ne peut pas être envoyée par email.', $application));
        }

        $user = $this->userRepository->findOneBy([]);
        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom, $this->senderName()))
            ->to(trim($application->getCompany()->getContactEmail()))
            ->subject($this->subject($application))
            ->htmlTemplate(self::HTML_TEMPLATE)
            ->textTemplate(self::TEXT_TEMPLATE)
            ->context($this->context($application))
            ->attach($this->cvPdfGenerator->render(), $this->cvPdfGenerator->filename(), 'application/pdf');

        if ($user?->getEmail()) {
            $email->replyTo(new Address($user->getEmail(), $this->senderName()));
        }

        $this->mailer->send($email);
        $application->markAsSent();
    }

    public function subject(JobApplication $application): string
    {
        return $this->coverLetterGenerator->subject($application);
    }

    /** Rendu HTML du corps, pour l'aperçu avant envoi. */
    public function renderHtml(JobApplication $application): string
    {
        return $this->twig->render(self::HTML_TEMPLATE, $this->context($application));
    }

    private function context(JobApplication $application): array
    {
        // Même découpage en paragraphes que le PDF de la lettre
        $body = str_replace("\r\n", "\n", (string) $application->getCoverLetter());

        return [
            'user' => $this->userRepository->findOneBy([]),
            'paragraphs' => array_values(array_filter(array_map('trim', explode("\n\n", $body)))),
        ];
    }

    private function senderName(): string
    {
        $user = $this->userRepository->findOneBy([]);

        return trim(sprintf('%s %s', $user?->getFirstName(), $user?->getLastName()));
    }
}
