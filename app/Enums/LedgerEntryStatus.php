<?php

namespace App\Enums;

enum LedgerEntryStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
