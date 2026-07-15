<?php

namespace App\Enum;

enum JobOfferStatus: string
{
    case ToReview = 'to_review';
    case Applied = 'applied';
    case FollowUp = 'follow_up';
    case Interview = 'interview';
    case Rejected = 'rejected';
    case Accepted = 'accepted';

    public function label(): string
    {
        return match($this) {
            self::ToReview => 'À étudier',
            self::Applied => 'Postulé',
            self::FollowUp => 'Relancé',
            self::Interview => 'Entretien',
            self::Rejected => 'Refusé',
            self::Accepted => 'Accepté',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ToReview => 'secondary',
            self::Applied => 'info',
            self::FollowUp => 'warning',
            self::Interview => 'primary',
            self::Rejected => 'danger',
            self::Accepted => 'success',
        };
    }
}