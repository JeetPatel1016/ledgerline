<?php

declare(strict_types=1);

namespace App\Domain\Ledger;

enum TransactionKind: string
{
    case Transfer = 'transfer';
    case Hold = 'hold';
    case Capture = 'capture';
    case Void = 'void';
    case Fee = 'fee';
    case Fx = 'fx';
    case Reversal = 'reversal';
}
