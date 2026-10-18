<?php

namespace App\Enums;

enum LedgerEntryType: string
{
    case Disbursement = 'disbursement';
    case Collection = 'collection';
    case Fee = 'fee';
    case Reversal = 'reversal';
}
