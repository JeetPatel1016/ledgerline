<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Ledger\Account;
use App\Domain\Ledger\AccountType;
use App\Domain\Ledger\Currency;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        foreach (Currency::cases() as $currency) {
            Account::firstOrCreate(['code' => 'fees_'.strtolower($currency->value)], [
                'name' => "Fees {$currency->value}",
                'type' => AccountType::Revenue,
                'currency' => $currency,
            ]);
        }
    }
}
