<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fabrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('roll_code')->unique();
            $table->string('name');
            $table->string('color')->nullable();
            $table->string('supplier')->nullable();
            $table->date('date_received')->nullable();
            $table->decimal('claimed_meters', 10, 2)->nullable();
            $table->decimal('verified_meters', 10, 2)->nullable();
            $table->decimal('buying_price', 12, 2)->nullable();
            $table->decimal('selling_price_per_meter', 12, 2)->nullable();
            $table->string('width')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fabrics');
    }
};
