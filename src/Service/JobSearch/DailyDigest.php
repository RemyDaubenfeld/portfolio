<?php

namespace App\Service\JobSearch;

use App\Enum\JobApplicationStatus;
use App\Repository\AdminUserRepository;
use App\Repository\JobApplicationRepository;
use App\Repository\JobOfferRepository;
use App\Repository\SettingRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Récapitulatif quotidien de la recherche d'emploi, envoyé par email à l'admin.
 */
class DailyDigest
{
    /** Relances à venir affichées dans le récapitulatif (en plus des relances dues). */
    private const UPCOMING_DAYS = 3;

    /** Clé de la table setting : destinataire(s) du récapitulatif, séparés par des virgules. */
    private const RECIPIENT_SETTING = 'job_search_digest_recipient';

    public function __construct(
        private readonly JobApplicationRepository $applicationRepository,
        private readonly JobOfferRepository $offerRepository,
        private readonly AdminUserRepository $adminUserRepository,
        private readonly SettingRepository $settingRepository,
        private readonly MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM)%')] private readonly string $mailerFrom,
    ) {
    }

    /**
     * @return array{followUpsDue: array, upcomingFollowUps: array, newOffers: array, interviews: array}
     */
    public function collect(\DateTimeImmutable $since): array
    {
        $now = new \DateTimeImmutable();
        $limit = $now->modify(sprintf('+%d days', self::UPCOMING_DAYS))->setTime(23, 59, 59);

        return [
            'followUpsDue' => $this->applicationRepository->findFollowUpsDue(),
            'upcomingFollowUps' => array_values(array_filter(
                $this->applicationRepository->findAwaitingResponse(),
                fn ($a) => $a->getFollowUpAt() > $now && $a->getFollowUpAt() <= $limit,
            )),
            'newOffers' => $this->offerRepository->findToReviewCreatedSince($since),
            'interviews' => $this->applicationRepository->findByStatus(JobApplicationStatus::Interview),
        ];
    }

    /** Un récapitulatif sans relance due ni nouvelle offre n'apporte rien : il n'est pas envoyé. */
    public function hasNews(array $digest): bool
    {
        return $digest['followUpsDue'] || $digest['newOffers'];
    }

    /** @return string le ou les destinataires */
    public function send(array $digest): string
    {
        $recipients = $this->recipients();
        if (!$recipients) {
            throw new \RuntimeException('Aucun destinataire pour le récapitulatif : renseigner le paramètre "' . self::RECIPIENT_SETTING . '" ou créer un compte admin.');
        }

        $this->mailer->send((new TemplatedEmail())
            ->from(new Address($this->mailerFrom, 'Portfolio — Recherche d\'emploi'))
            ->to(...$recipients)
            ->subject($this->subject($digest))
            ->htmlTemplate('emails/job_search_digest.html.twig')
            ->context($digest));

        return implode(', ', $recipients);
    }

    /**
     * Adresse(s) du paramètre "job_search_digest_recipient", sinon l'email du compte admin.
     *
     * @return string[]
     */
    private function recipients(): array
    {
        $configured = (string) $this->settingRepository->find(self::RECIPIENT_SETTING)?->getValue();
        $recipients = array_values(array_filter(array_map('trim', explode(',', $configured))));

        if (!$recipients && ($adminEmail = $this->adminUserRepository->findOneBy([], ['id' => 'ASC'])?->getEmail())) {
            $recipients = [$adminEmail];
        }

        return $recipients;
    }

    private function subject(array $digest): string
    {
        $parts = [];
        if ($count = count($digest['followUpsDue'])) {
            $parts[] = sprintf('%d relance%s à faire', $count, $count > 1 ? 's' : '');
        }
        if ($count = count($digest['newOffers'])) {
            $parts[] = sprintf('%d nouvelle%s offre%s', $count, $count > 1 ? 's' : '', $count > 1 ? 's' : '');
        }

        return 'Recherche d\'emploi : ' . ($parts ? implode(', ', $parts) : 'rien de neuf');
    }
}
