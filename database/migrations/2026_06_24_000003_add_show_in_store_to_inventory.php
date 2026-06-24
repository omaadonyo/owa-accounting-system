<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products_services', function (Blueprint $table) {
            $table->boolean('show_in_store')->default(false)->after('image');
            $table->text('store_description')->nullable()->after('show_in_store');
        });

        Schema::table('fabrics', function (Blueprint $table) {
            $table->boolean('show_in_store')->default(false)->after('image');
            $table->text('store_description')->nullable()->after('show_in_store');
        });
    }

    public function down(): void
    {
        Schema::table('products_services', function (Blueprint $table) {
            $table->dropColumn(['show_in_store', 'store_description']);
        });

        Schema::table('fabrics', function (Blueprint $table) {
            $table->dropColumn(['show_in_store', 'store_description']);
        });
    }
};
