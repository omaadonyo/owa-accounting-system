<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_quotations', function (Blueprint $table) {
            $table->dropForeign(['fabric_id']);
            $table->renameColumn('fabric_id', 'item_id');
        });

        Schema::table('customer_quotations', function (Blueprint $table) {
            $table->string('item_type')->default('fabric')->after('item_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_quotations', function (Blueprint $table) {
            $table->dropColumn('item_type');
        });

        Schema::table('customer_quotations', function (Blueprint $table) {
            $table->renameColumn('item_id', 'fabric_id');
            $table->foreign('fabric_id')->references('id')->on('fabrics')->cascadeOnDelete();
        });
    }
};
