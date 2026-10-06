<?php

namespace App\Enum;

enum JobApplicationType: string
{
    case JobPosting = 'job_posting';
    case Spontaneous = 'spontaneous';

    public function label(): string
    {
        return match($this) {
            self::JobPosting => 'Annonce',
            self::Spontaneous => 'Spontanée',
        };
    }
}
