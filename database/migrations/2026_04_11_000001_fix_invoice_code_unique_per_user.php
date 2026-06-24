<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            // Constraint creato con nome PostgreSQL nativo → SQL diretto.
            DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_invoice_code_key');
            DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_invoice_code_unique');
            DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_user_id_invoice_code_unique UNIQUE (user_id, invoice_code)');

            return;
        }

        // mysql / mariadb — DROP CONSTRAINT IF EXISTS is not supported; use Blueprint.
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_invoice_code_unique'); // index from ->unique() on invoice_code
            $table->unique(['user_id', 'invoice_code'], 'invoices_user_id_invoice_code_unique');
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_user_id_invoice_code_unique');
            DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_invoice_code_key UNIQUE (invoice_code)');

            return;
        }

        // mysql / mariadb
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_user_id_invoice_code_unique');
            $table->unique('invoice_code', 'invoices_invoice_code_unique');
        });
    }
};
