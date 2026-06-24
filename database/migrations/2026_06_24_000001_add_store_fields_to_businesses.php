<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable()->after('name');
            $table->boolean('store_active')->default(false)->after('currency');
            $table->string('store_font')->default('Inter')->after('store_active');
            $table->string('store_primary_color', 7)->default('#4f46e5')->after('store_font');
            $table->string('store_accent_color', 7)->default('#f59e0b')->after('store_primary_color');
            $table->string('store_headline')->nullable()->after('store_accent_color');
            $table->text('store_subheadline')->nullable()->after('store_headline');
            $table->text('store_about_text')->nullable()->after('store_subheadline');
            $table->string('store_hero_image')->nullable()->after('store_about_text');
            $table->boolean('store_show_products')->default(true)->after('store_hero_image');
            $table->boolean('store_show_about')->default(true)->after('store_show_products');
            $table->boolean('store_show_contact')->default(true)->after('store_show_about');
            $table->string('store_contact_email')->nullable()->after('store_show_contact');
            $table->string('store_contact_phone')->nullable()->after('store_contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'slug', 'store_active', 'store_font', 'store_primary_color', 'store_accent_color',
                'store_headline', 'store_subheadline', 'store_about_text', 'store_hero_image',
                'store_show_products', 'store_show_about', 'store_show_contact',
                'store_contact_email', 'store_contact_phone',
            ]);
        });
    }
};
