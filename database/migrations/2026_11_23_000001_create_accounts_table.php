<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->char('currency', 3);
            $table->boolean('allow_negative')->default(false);
            // Cached sum of entries in minor units, signed by the account type's normal direction.
            $table->bigInteger('balance')->default(0);
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE accounts ADD CONSTRAINT accounts_type_check CHECK (type IN ('asset', 'liability', 'equity', 'revenue', 'expense'))");
        DB::statement("ALTER TABLE accounts ADD CONSTRAINT accounts_currency_check CHECK (currency IN ('CAD', 'USD'))");
        DB::statement('ALTER TABLE accounts ADD CONSTRAINT accounts_non_negative CHECK (allow_negative OR balance >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
