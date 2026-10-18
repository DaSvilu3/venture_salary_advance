<?php

namespace App\Enums;

enum MandateStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Cancelled = 'cancelled';
}
