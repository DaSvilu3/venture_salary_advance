<?php

namespace App\Enums;

/**
 * The platform review state of an uploaded company document.
 */
enum DocumentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
