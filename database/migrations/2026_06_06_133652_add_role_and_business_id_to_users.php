<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('remember_token');
            $table->foreignId('business_id')->nullable()->constrained()->after('role');

            $table->index('role');
        });

        DB::statement('UPDATE users SET business_id = (SELECT id FROM businesses WHERE businesses.user_id = users.id) WHERE EXISTS (SELECT 1 FROM businesses WHERE businesses.user_id = users.id)');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'business_id']);
        });
    }
};
