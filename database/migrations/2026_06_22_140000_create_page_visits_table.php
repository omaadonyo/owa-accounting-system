<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 150)->nullable();
            $table->string('region', 150)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('visited_url')->nullable();
            $table->string('referrer_url')->nullable();
            $table->timestamp('visited_at')->useCurrent();
            $table->timestamps();

            $table->index('visited_at');
            $table->index('country');
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
