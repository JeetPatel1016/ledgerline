<?php

declare(strict_types=1);

/*
 * One withdrawal in its own process (so its own DB connection), started by
 * ParallelWithdrawalTest. Waits until a shared start time so all processes
 * hit the ledger together. Exit 0 = posted, 3 = insufficient funds, 1 = anything else.
 *
 * Usage: php withdraw.php <from-id> <to-id> <amount> <idempotency-key> <start-unix-time>
 */

use App\Domain\Ledger\Account;
use App\Domain\Ledger\Currency;
use App\Domain\Ledger\LedgerException;
use App\Domain\Payments\TransferService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $fromId, $toId, $amount, $key, $startAt] = $argv;
$from = Account::findOrFail((int) $fromId);
$to = Account::findOrFail((int) $toId);

if ((float) $startAt > microtime(true)) {
    time_sleep_until((float) $startAt);
}

try {
    $app->make(TransferService::class)->transfer($from, $to, (int) $amount, Currency::CAD, 0, $key);
    exit(0);
} catch (LedgerException $e) {
    if ($e->errorCode === 'insufficient_funds') {
        exit(3);
    }
    fwrite(STDERR, $e->getMessage());
    exit(1);
}
