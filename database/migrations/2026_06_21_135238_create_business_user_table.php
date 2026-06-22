<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('admin');
            $table->timestamps();

            $table->unique(['user_id', 'business_id']);
        });

        // Seed pivot from existing owner and member relationships
        DB::statement("
            INSERT IGNORE INTO business_user (user_id, business_id, role, created_at, updated_at)
            SELECT u.id, b.id, COALESCE(u.role, 'admin'), NOW(), NOW()
            FROM businesses b
            JOIN users u ON (u.id = b.user_id OR u.business_id = b.id)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('business_user');
    }
};
