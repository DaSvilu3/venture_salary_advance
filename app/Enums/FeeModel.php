<?php

namespace App\Enums;

/**
 * How the fee on an advance is calculated. Only a flat service fee exists for now; a Sharia-compliant (ujrah) variant would be added here.
 */
enum FeeModel: string
{
    case Flat = 'flat';
}
