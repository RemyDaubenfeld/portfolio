<?php

namespace App\Enum;

enum CompanySource: string
{
    case ItList = 'it_list';
    case AutoOffer = 'auto_offer';
    case Manual = 'manual';

    public function label(): string
    {
        return match($this) {
            self::ItList => 'Liste IT',
            self::AutoOffer => 'Offre (auto)',
            self::Manual => 'Manuel',
        };
    }
}
