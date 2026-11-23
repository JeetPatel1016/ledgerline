<?php

declare(strict_types=1);

namespace App\Domain\Ledger;

enum TransactionStatus: string
{
    case Posted = 'posted';
    case Pending = 'pending';
    case Voided = 'voided';
}
