<?php

declare(strict_types=1);

namespace App\Domain\Ledger;

enum Currency: string
{
    case CAD = 'CAD';
    case USD = 'USD';
}
