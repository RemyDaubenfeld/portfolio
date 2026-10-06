<?php

namespace App\Enum;

enum CompanyStatus: string
{
    case ToQualify = 'to_qualify';
    case ToContact = 'to_contact';
    case Contacted = 'contacted';
    case NoResponse = 'no_response';

    public function label(): string
    {
        return match($this) {
            self::ToQualify => 'À qualifier',
            self::ToContact => 'À contacter',
            self::Contacted => 'Contactée',
            self::NoResponse => 'Sans réponse',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ToQualify => 'warning',
            self::ToContact => 'info',
            self::Contacted => 'success',
            self::NoResponse => 'secondary',
        };
    }
}
