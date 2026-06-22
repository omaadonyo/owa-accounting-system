<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE quotation_items MODIFY description TEXT NOT NULL');
        DB::statement('ALTER TABLE invoice_items MODIFY description TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE quotation_items MODIFY description VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE invoice_items MODIFY description VARCHAR(255) NOT NULL');
    }
};
