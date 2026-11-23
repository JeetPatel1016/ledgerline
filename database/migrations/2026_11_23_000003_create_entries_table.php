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
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained();
            $table->foreignId('account_id')->constrained();
            $table->string('direction');
            $table->bigInteger('amount');
            $table->char('currency', 3);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['account_id', 'id']);
        });

        DB::statement("ALTER TABLE entries ADD CONSTRAINT entries_direction_check CHECK (direction IN ('debit', 'credit'))");
        DB::statement('ALTER TABLE entries ADD CONSTRAINT entries_amount_positive CHECK (amount > 0)');

        // Entries are append-only: corrections are reversing transactions.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION forbid_change() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION '% is append-only: % is not allowed', TG_TABLE_NAME, TG_OP;
            END $$;

            CREATE TRIGGER entries_no_update_delete BEFORE UPDATE OR DELETE ON entries
                FOR EACH ROW EXECUTE FUNCTION forbid_change();
            CREATE TRIGGER entries_no_truncate BEFORE TRUNCATE ON entries
                FOR EACH STATEMENT EXECUTE FUNCTION forbid_change();
        SQL);

        // Checked at commit, once all of a transaction's entries are in:
        // at least two entries, and debits = credits for every currency.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION check_transaction_balanced() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF (SELECT count(*) FROM entries WHERE transaction_id = NEW.transaction_id) < 2 THEN
                    RAISE EXCEPTION 'transaction % has fewer than two entries', NEW.transaction_id;
                END IF;
                IF EXISTS (
                    SELECT 1 FROM entries
                    WHERE transaction_id = NEW.transaction_id
                    GROUP BY currency
                    HAVING sum(CASE WHEN direction = 'debit' THEN amount ELSE -amount END) <> 0
                ) THEN
                    RAISE EXCEPTION 'transaction % is unbalanced', NEW.transaction_id;
                END IF;
                RETURN NULL;
            END $$;

            CREATE CONSTRAINT TRIGGER entries_balanced AFTER INSERT ON entries
                DEFERRABLE INITIALLY DEFERRED
                FOR EACH ROW EXECUTE FUNCTION check_transaction_balanced();
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
        DB::unprepared('DROP FUNCTION IF EXISTS check_transaction_balanced(); DROP FUNCTION IF EXISTS forbid_change() CASCADE;');
    }
};
