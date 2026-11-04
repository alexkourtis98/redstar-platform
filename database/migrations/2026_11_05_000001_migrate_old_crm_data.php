<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migrate data from old CRM (MySQL `manageredstarweb` on same server).
     */
    public function up(): void
    {
        // Check if old_crm connection is configured
        if (!config('database.connections.old_crm.username')) {
            $this->command->warn('OLD_CRM_DB_USERNAME not set. Skipping data migration.');
            $this->command->info('To migrate data, add these to .env:');
            $this->command->info('  OLD_CRM_DB_HOST=127.0.0.1');
            $this->command->info('  OLD_CRM_DB_DATABASE=manageredstarweb');
            $this->command->info('  OLD_CRM_DB_USERNAME=manageredstarwebuseradmindb');
            $this->command->info('  OLD_CRM_DB_PASSWORD=<password>');
            return;
        }

        // Test connection
        try {
            DB::connection('old_crm')->getPdo();
        } catch (\Exception $e) {
            $this->command->error('Cannot connect to old CRM database: ' . $e->getMessage());
            $this->command->warn('Skipping data migration.');
            return;
        }

        $this->command->info('Connected to old CRM database (manageredstarweb)');

        // Get RedStar entity ID (always entity_id = 1 from ProductionEntitySeeder)
        $redstarId = DB::table('entities')->where('slug', 'redstar')->value('id');

        if (!$redstarId) {
            throw new \Exception('RedStar entity not found. Run ProductionEntitySeeder first.');
        }

        $this->command->info("RedStar entity ID: {$redstarId}");

        $oldDb = DB::connection('old_crm');

        // 1. Migrate Clients
        $this->command->info('Migrating clients...');
        $clients = $oldDb->table('clients')->whereNull('deleted_at')->get();

        foreach ($clients as $client) {
            DB::table('clients')->insert([
                'id' => $client->id,
                'entity_id' => $redstarId,
                'name' => $client->name,
                'company_name' => $client->company_name ?? null,
                'email' => $client->email ?? null,
                'phone' => $client->phone ?? null,
                'vat_number' => $client->vat_number ?? null,
                'tax_office' => $client->tax_office ?? null,
                'address' => $client->address ?? null,
                'city' => $client->city ?? null,
                'postal_code' => $client->postal_code ?? null,
                'country' => $client->country ?? 'GR',
                'status' => ($client->status ?? 'lead') === 'lead' ? 'active' : ($client->status ?? 'active'),
                'source' => $client->source ?? null,
                'notes' => $client->notes ?? null,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at,
            ]);
        }
        $this->command->info("Migrated {$clients->count()} clients");

        // 2. Migrate Projects
        $this->command->info('Migrating projects...');
        $projects = $oldDb->table('projects')->whereNull('deleted_at')->get();

        foreach ($projects as $project) {
            DB::table('projects')->insert([
                'id' => $project->id,
                'entity_id' => $redstarId,
                'client_id' => $project->client_id,
                'title' => $project->title,
                'description' => $project->description ?? null,
                'type' => $project->type ?? 'other',
                'status' => $project->status ?? 'active',
                'priority' => $project->priority ?? 'medium',
                'price' => $project->price ?? null,
                'currency' => $project->currency ?? 'EUR',
                'start_date' => $project->start_date ?? null,
                'end_date' => $project->deadline ?? null,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
            ]);
        }
        $this->command->info("Migrated {$projects->count()} projects");

        // 3. Migrate Tasks (if table exists)
        if ($this->oldTableExists($oldDb, 'tasks')) {
            $this->command->info('Migrating tasks...');
            $tasks = $oldDb->table('tasks')->get();

            foreach ($tasks as $task) {
                $status = ($task->is_completed ?? false) ? 'completed' : 'pending';

                DB::table('tasks')->insert([
                    'id' => $task->id,
                    'project_id' => $task->project_id,
                    'title' => $task->title,
                    'description' => $task->description ?? null,
                    'status' => $status,
                    'due_date' => $task->due_date ?? null,
                    'assigned_to' => null,
                    'sort_order' => $task->sort_order ?? 0,
                    'created_at' => $task->created_at,
                    'updated_at' => $task->updated_at,
                ]);
            }
            $this->command->info("Migrated {$tasks->count()} tasks");
        } else {
            $this->command->warn('No tasks table in old CRM (skipping)');
        }

        // 4. Migrate Payments
        $this->command->info('Migrating payments...');
        $payments = $oldDb->table('payments')->get();

        foreach ($payments as $payment) {
            $clientId = DB::table('projects')
                ->where('id', $payment->project_id)
                ->value('client_id');

            DB::table('payments')->insert([
                'id' => $payment->id,
                'entity_id' => $redstarId,
                'client_id' => $clientId,
                'project_id' => $payment->project_id,
                'subscription_id' => null,
                'type' => 'full',
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'EUR',
                'gateway' => null,
                'gateway_payment_id' => null,
                'gateway_customer_id' => null,
                'status' => 'paid',
                'payment_link_token' => null,
                'link_expires_at' => null,
                'metadata' => json_encode([
                    'method' => $payment->method ?? 'manual',
                    'reference' => $payment->reference ?? null,
                    'notes' => $payment->notes ?? null,
                    'migrated_from_old_crm' => true,
                ]),
                'paid_at' => $payment->payment_date ?? $payment->created_at,
                'refunded_at' => null,
                'failure_reason' => null,
                'created_at' => $payment->created_at,
                'updated_at' => $payment->updated_at,
            ]);
        }
        $this->command->info("Migrated {$payments->count()} payments");

        // 5. Migrate Contacts (if table exists)
        if ($this->oldTableExists($oldDb, 'contacts')) {
            $contacts = $oldDb->table('contacts')->get();

            if ($contacts->count() > 0) {
                $this->command->info('Migrating contacts...');

                foreach ($contacts as $contact) {
                    DB::table('contacts')->insert([
                        'id' => $contact->id,
                        'client_id' => $contact->client_id,
                        'name' => $contact->name,
                        'email' => $contact->email ?? null,
                        'phone' => $contact->phone ?? null,
                        'position' => $contact->position ?? null,
                        'is_primary' => $contact->is_primary ?? false,
                        'created_at' => $contact->created_at,
                        'updated_at' => $contact->updated_at,
                    ]);
                }
                $this->command->info("Migrated {$contacts->count()} contacts");
            }
        } else {
            $this->command->warn('No contacts table in old CRM (skipping)');
        }

        // Summary
        $this->command->info("\n=== Migration Summary ===");
        $this->command->info("Clients: " . DB::table('clients')->count());
        $this->command->info("Projects: " . DB::table('projects')->count());
        $this->command->info("Tasks: " . DB::table('tasks')->count());
        $this->command->info("Payments: " . DB::table('payments')->count());

        if (Schema::hasTable('contacts')) {
            $this->command->info("Contacts: " . DB::table('contacts')->count());
        }

        $this->command->info("\nMigration completed successfully!");
    }

    /**
     * Check if a table exists on the old CRM connection.
     */
    private function oldTableExists($connection, string $table): bool
    {
        try {
            $connection->table($table)->limit(1)->get();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->command->warn('This migration cannot be reversed automatically.');
        $this->command->warn('Restore from backup if needed.');
    }
};
