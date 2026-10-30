<?php

namespace App\Enums;

/**
 * The employer's decision on the programme agreement.
 */
enum AgreementStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
