<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::getConnection()->statement("ALTER TABLE products_services MODIFY COLUMN type ENUM('product', 'service', 'office_rent') NOT NULL");
    }

    public function down(): void
    {
        Schema::getConnection()->statement("ALTER TABLE products_services MODIFY COLUMN type ENUM('product', 'service') NOT NULL");
    }
};
