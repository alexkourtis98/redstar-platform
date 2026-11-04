<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Upgrade clients and projects tables to add entity_id.
     * This runs BEFORE the payments schema upgrade.
     */
    public function up(): void
    {
        // Get RedStar entity ID (should be 1 from seeder)
        $redstarId = DB::table('entities')->where('slug', 'redstar')->value('id');

        if (!$redstarId) {
            $this->command->warn('RedStar entity not found. Using entity_id = 1');
            $redstarId = 1;
        }

        // === CLIENTS TABLE ===
        $this->command->info('Upgrading clients table schema...');

        Schema::table('clients', function (Blueprint $table) {
            // Add entity_id column (nullable first, will make NOT NULL after populating)
            $table->foreignId('entity_id')->nullable()->after('id')->constrained()->onDelete('cascade');

            // Update status enum to match new schema
            // Old: 'lead' (default), new: 'active', 'inactive', 'archived'
            // We'll convert 'lead' status to 'active'
        });

        // Populate entity_id for all existing clients
        DB::table('clients')->whereNull('entity_id')->update(['entity_id' => $redstarId]);

        // Make entity_id NOT NULL
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('entity_id')->nullable(false)->change();
        });

        // Add index
        Schema::table('clients', function (Blueprint $table) {
            $table->index(['entity_id', 'status']);
        });

        $clientCount = DB::table('clients')->count();
        $this->command->info("Updated {$clientCount} clients with entity_id = {$redstarId}");

        // === PROJECTS TABLE ===
        $this->command->info('Upgrading projects table schema...');

        Schema::table('projects', function (Blueprint $table) {
            // Add entity_id column
            $table->foreignId('entity_id')->nullable()->after('id')->constrained()->onDelete('cascade');

            // Add end_date if it doesn't exist (prod has 'deadline', new schema has 'end_date')
            if (!Schema::hasColumn('projects', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
        });

        // Populate entity_id for all existing projects
        DB::table('projects')->whereNull('entity_id')->update(['entity_id' => $redstarId]);

        // Copy deadline to end_date if end_date is null
        DB::table('projects')
            ->whereNotNull('deadline')
            ->whereNull('end_date')
            ->update(['end_date' => DB::raw('deadline')]);

        // Make entity_id NOT NULL
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('entity_id')->nullable(false)->change();
        });

        // Add index
        Schema::table('projects', function (Blueprint $table) {
            $table->index(['entity_id', 'status']);
        });

        $projectCount = DB::table('projects')->count();
        $this->command->info("Updated {$projectCount} projects with entity_id = {$redstarId}");

        $this->command->info('Clients and projects schema upgrade completed!');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn('entity_id');
            $table->dropIndex(['entity_id', 'status']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn(['entity_id', 'end_date']);
            $table->dropIndex(['entity_id', 'status']);
        });
    }
};
