<?php

namespace App\Enum;

enum JobApplicationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case FollowedUp = 'followed_up';
    case Interview = 'interview';
    case Rejected = 'rejected';
    case NoResponse = 'no_response';
    case OfferReceived = 'offer_received';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Brouillon',
            self::Sent => 'Envoyée',
            self::FollowedUp => 'Relancée',
            self::Interview => 'Entretien',
            self::Rejected => 'Refus',
            self::NoResponse => 'Sans réponse',
            self::OfferReceived => 'Offre reçue',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft => 'secondary',
            self::Sent => 'info',
            self::FollowedUp => 'warning',
            self::Interview => 'primary',
            self::Rejected => 'danger',
            self::NoResponse => 'dark',
            self::OfferReceived => 'success',
        };
    }

    /** Candidature en attente d'une réponse de l'entreprise (éligible aux relances). */
    public function isAwaitingResponse(): bool
    {
        return in_array($this, [self::Sent, self::FollowedUp], true);
    }

    /** L'entreprise a répondu (positivement ou non). */
    public function isAnswered(): bool
    {
        return in_array($this, [self::Interview, self::Rejected, self::OfferReceived], true);
    }

    /** Statut à reporter sur l'offre liée, null si l'offre ne doit pas changer. */
    public function toJobOfferStatus(): ?JobOfferStatus
    {
        return match($this) {
            self::Draft, self::NoResponse => null,
            self::Sent => JobOfferStatus::Applied,
            self::FollowedUp => JobOfferStatus::FollowUp,
            self::Interview => JobOfferStatus::Interview,
            self::Rejected => JobOfferStatus::Rejected,
            self::OfferReceived => JobOfferStatus::Accepted,
        };
    }
}
