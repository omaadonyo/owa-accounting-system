<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Rename event_type to action if event_type exists
            if (Schema::hasColumn('activity_logs', 'event_type')) {
                DB::statement('ALTER TABLE activity_logs CHANGE event_type action VARCHAR(255) NOT NULL');
            }

            // Add missing columns if they don't exist
            if (!Schema::hasColumn('activity_logs', 'business_id')) {
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete()->after('user_id');
            }

            if (!Schema::hasColumn('activity_logs', 'subject_type')) {
                $table->string('subject_type')->nullable()->after('action');
            }

            if (!Schema::hasColumn('activity_logs', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            }

            if (!Schema::hasColumn('activity_logs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }

            // Add indexes if they don't exist
            if (!Schema::hasIndex('activity_logs', 'activity_logs_subject_type_subject_id_index')) {
                $table->index(['subject_type', 'subject_id']);
            }
            if (!Schema::hasIndex('activity_logs', 'activity_logs_action_index')) {
                $table->index('action');
            }
            if (!Schema::hasIndex('activity_logs', 'activity_logs_created_at_index')) {
                $table->index('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (Schema::hasColumn('activity_logs', 'action') && !Schema::hasColumn('activity_logs', 'event_type')) {
                DB::statement('ALTER TABLE activity_logs CHANGE action event_type VARCHAR(255) NOT NULL');
            }

            if (Schema::hasColumn('activity_logs', 'business_id')) {
                $table->dropForeign(['business_id']);
                $table->dropColumn('business_id');
            }

            $table->dropColumn(['subject_type', 'subject_id', 'updated_at']);
        });
    }
};
