<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fabrics', function (Blueprint $table) {
            $table->decimal('used_meters', 10, 2)->default(0)->after('verified_meters');
        });

        Schema::table('products_services', function (Blueprint $table) {
            $table->decimal('buying_price', 12, 2)->nullable()->after('sku');
            $table->renameColumn('price', 'selling_price');
        });
    }

    public function down(): void
    {
        Schema::table('fabrics', function (Blueprint $table) {
            $table->dropColumn('used_meters');
        });

        Schema::table('products_services', function (Blueprint $table) {
            $table->dropColumn('buying_price');
            $table->renameColumn('selling_price', 'price');
        });
    }
};
