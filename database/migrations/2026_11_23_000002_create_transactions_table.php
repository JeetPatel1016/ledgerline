<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('kind');
            $table->string('status');
            $table->string('idempotency_key')->unique();
            // Unique: a transaction can be reversed at most once.
            $table->foreignId('reverses_id')->nullable()->unique()->constrained('transactions');
            $table->jsonb('metadata')->default('{}');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
