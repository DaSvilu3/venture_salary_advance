<?php

namespace App\Enums;

enum EnrolmentStatus: string
{
    case Invited = 'invited';
    case PendingApproval = 'pending_approval';
    case Enrolled = 'enrolled';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
}
