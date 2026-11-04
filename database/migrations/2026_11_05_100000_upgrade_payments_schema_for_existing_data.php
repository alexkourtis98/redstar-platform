<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Upgrade existing payments table from old schema (March 2026) to new schema (Nov 2026).
     * This migration adds missing columns and migrates existing data.
     */
    public function up(): void
    {
        // Add missing columns to existing payments table
        Schema::table('payments', function (Blueprint $table) {
            // Add entity_id (will populate with RedStar entity = 1)
            $table->foreignId('entity_id')->nullable()->after('id')->constrained()->onDelete('cascade');

            // Add client_id (will derive from project)
            $table->foreignId('client_id')->nullable()->after('entity_id')->constrained()->nullOnDelete();

            // Add subscription_id for future use
            $table->foreignId('subscription_id')->nullable()->after('project_id')->constrained()->nullOnDelete();

            // Add type enum
            $table->enum('type', ['deposit', 'full', 'package', 'subscription'])->default('full')->after('subscription_id');

            // Modify existing gateway column to enum (currently string or NULL)
            // Keep existing values, make nullable
            if (!Schema::hasColumn('payments', 'gateway_customer_id')) {
                $table->string('gateway_customer_id')->nullable()->after('gateway_payment_id');
            }

            // Add payment_link_token if doesn't exist (it should from earlier migration)
            if (!Schema::hasColumn('payments', 'payment_link_token')) {
                $table->string('payment_link_token')->nullable()->unique()->after('status');
            }

            // Add link_expires_at if doesn't exist
            if (!Schema::hasColumn('payments', 'link_expires_at')) {
                $table->timestamp('link_expires_at')->nullable()->after('payment_link_token');
            }

            // Add metadata JSON column
            if (!Schema::hasColumn('payments', 'metadata')) {
                $table->json('metadata')->nullable()->after('link_expires_at');
            }

            // Add paid_at timestamp
            if (!Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('metadata');
            }

            // Add refunded_at timestamp
            if (!Schema::hasColumn('payments', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('paid_at');
            }

            // Add failure_reason
            if (!Schema::hasColumn('payments', 'failure_reason')) {
                $table->string('failure_reason')->nullable()->after('refunded_at');
            }

            // Add soft deletes
            $table->softDeletes();
        });

        // Populate new columns with data from existing records
        $this->command->info('Migrating existing payment data to new schema...');

        // Get RedStar entity ID (should be 1 from seeder)
        $redstarId = DB::table('entities')->where('slug', 'redstar')->value('id');

        if (!$redstarId) {
            $this->command->warn('RedStar entity not found. Using entity_id = 1');
            $redstarId = 1;
        }

        // Get all payments
        $payments = DB::table('payments')->get();

        foreach ($payments as $payment) {
            // Derive client_id from project
            $clientId = null;
            if ($payment->project_id) {
                $clientId = DB::table('projects')
                    ->where('id', $payment->project_id)
                    ->value('client_id');
            }

            // Build metadata from old fields
            $metadata = [
                'method' => $payment->method ?? 'bank_transfer',
                'reference' => $payment->reference ?? null,
                'migrated_from_old_crm' => true,
            ];

            // Add old notes to metadata if exists and not already in new notes column
            if (!empty($payment->notes)) {
                $metadata['original_notes'] = $payment->notes;
            }

            // Update record
            DB::table('payments')
                ->where('id', $payment->id)
                ->update([
                    'entity_id' => $redstarId,
                    'client_id' => $clientId,
                    'type' => 'full', // All existing payments are full payments
                    'paid_at' => $payment->payment_date ?? $payment->created_at,
                    'metadata' => json_encode($metadata),
                    // status already set to 'completed', keep it
                ]);
        }

        $this->command->info("Migrated {$payments->count()} payments to new schema");

        // Make entity_id NOT NULL now that it's populated
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('entity_id')->nullable(false)->change();
        });

        // Add indexes for performance
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['entity_id', 'status', 'paid_at']);
            $table->index(['gateway', 'status']);
            $table->index('payment_link_token');
        });

        $this->command->info('Schema upgrade completed successfully!');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['subscription_id']);

            $table->dropColumn([
                'entity_id',
                'client_id',
                'subscription_id',
                'type',
                'gateway_customer_id',
                'payment_link_token',
                'link_expires_at',
                'metadata',
                'paid_at',
                'refunded_at',
                'failure_reason',
                'deleted_at',
            ]);

            $table->dropIndex(['entity_id', 'status', 'paid_at']);
            $table->dropIndex(['gateway', 'status']);
            $table->dropIndex(['payment_link_token']);
        });
    }
};
