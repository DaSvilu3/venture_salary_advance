<?php

namespace App\Enums;

enum PayCycleStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
