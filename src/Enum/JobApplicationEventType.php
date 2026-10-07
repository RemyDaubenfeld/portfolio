<?php

namespace App\Enum;

enum JobApplicationEventType: string
{
    case Created = 'created';
    case StatusChanged = 'status_changed';
    case FollowedUp = 'followed_up';

    public function icon(): string
    {
        return match($this) {
            self::Created => 'fa fa-plus',
            self::StatusChanged => 'fa fa-arrow-right',
            self::FollowedUp => 'fa fa-bell',
        };
    }
}
