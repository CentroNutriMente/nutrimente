<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Usa SQL diretto perché il constraint è stato creato con nome PostgreSQL nativo
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_invoice_code_key');
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_invoice_code_unique');
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_user_id_invoice_code_unique UNIQUE (user_id, invoice_code)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_user_id_invoice_code_unique');
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_invoice_code_key UNIQUE (invoice_code)');
    }
};
