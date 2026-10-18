<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case OnNotice = 'on_notice';
    case Terminated = 'terminated';
}
