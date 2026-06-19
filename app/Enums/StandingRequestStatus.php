<?php

declare(strict_types=1);

namespace App\Enums;

enum StandingRequestStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
