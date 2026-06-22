<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('show_discount_column')->default(true)->after('notes');
            $table->boolean('hide_total')->default(false)->after('show_discount_column');
            $table->string('custom_title')->nullable()->after('hide_total');
            $table->boolean('show_amount_in_words')->default(false)->after('custom_title');
            $table->boolean('act_as_delivery_note')->default(false)->after('show_amount_in_words');
            $table->boolean('tax_inclusive')->default(false)->after('act_as_delivery_note');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['show_discount_column', 'hide_total', 'custom_title', 'show_amount_in_words', 'act_as_delivery_note', 'tax_inclusive']);
        });
    }
};
