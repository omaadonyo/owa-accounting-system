<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old index that existed before event_type was renamed to action
        // It's now redundant with activity_logs_action_index
        Schema::table('activity_logs', function ($table) {
            if (Schema::hasIndex('activity_logs', 'activity_logs_event_type_index')) {
                $table->dropIndex('activity_logs_event_type_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function ($table) {
            $table->index('action', 'activity_logs_event_type_index');
        });
    }
};
