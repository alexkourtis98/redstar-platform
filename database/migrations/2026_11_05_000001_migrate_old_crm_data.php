<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Path to old SQLite database (update this when running on production)
        $oldDbPath = env('OLD_CRM_DB_PATH', '/tmp/old_crm.sqlite');

        if (!file_exists($oldDbPath)) {
            $this->command->warn("Old CRM database not found at: {$oldDbPath}");
            $this->command->info("Skipping data migration. To migrate data:");
            $this->command->info("1. Copy old database to server: scp ~/work/redstar/database/database.sqlite forge@64.227.66.233:/tmp/old_crm.sqlite");
            $this->command->info("2. Set OLD_CRM_DB_PATH=/tmp/old_crm.sqlite in .env");
            $this->command->info("3. Re-run this migration");
            return;
        }

        $this->command->info("Connecting to old CRM database at: {$oldDbPath}");
        $oldDb = new \PDO("sqlite:{$oldDbPath}");
        $oldDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Get RedStar entity ID (always entity_id = 1 from ProductionEntitySeeder)
        $redstarId = DB::table('entities')->where('slug', 'redstar')->value('id');

        if (!$redstarId) {
            throw new \Exception('RedStar entity not found. Run ProductionEntitySeeder first.');
        }

        $this->command->info("RedStar entity ID: {$redstarId}");

        // 1. Migrate Clients
        $this->command->info('Migrating clients...');
        $clients = $oldDb->query("SELECT * FROM clients WHERE deleted_at IS NULL")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($clients as $client) {
            DB::table('clients')->insert([
                'id' => $client['id'],
                'entity_id' => $redstarId,
                'name' => $client['name'],
                'company_name' => $client['company_name'],
                'email' => $client['email'],
                'phone' => $client['phone'],
                'vat_number' => $client['vat_number'],
                'tax_office' => $client['tax_office'],
                'address' => $client['address'],
                'city' => $client['city'],
                'postal_code' => $client['postal_code'],
                'country' => $client['country'] ?? 'GR',
                'status' => $client['status'] ?? 'active',
                'source' => $client['source'],
                'notes' => $client['notes'],
                'created_at' => $client['created_at'],
                'updated_at' => $client['updated_at'],
            ]);
        }
        $this->command->info("Migrated " . count($clients) . " clients");

        // 2. Migrate Projects
        $this->command->info('Migrating projects...');
        $projects = $oldDb->query("SELECT * FROM projects WHERE deleted_at IS NULL")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($projects as $project) {
            DB::table('projects')->insert([
                'id' => $project['id'],
                'entity_id' => $redstarId,
                'client_id' => $project['client_id'],
                'title' => $project['title'],
                'description' => $project['description'],
                'type' => $project['type'] ?? 'other',
                'status' => $project['status'] ?? 'active',
                'priority' => $project['priority'] ?? 'medium',
                'price' => $project['price'],
                'currency' => $project['currency'] ?? 'EUR',
                'start_date' => $project['start_date'],
                'end_date' => $project['deadline'], // Map 'deadline' to 'end_date'
                'created_at' => $project['created_at'],
                'updated_at' => $project['updated_at'],
            ]);
        }
        $this->command->info("Migrated " . count($projects) . " projects");

        // 3. Migrate Tasks
        $this->command->info('Migrating tasks...');
        $tasks = $oldDb->query("SELECT * FROM tasks")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($tasks as $task) {
            // Map is_completed to status
            $status = $task['is_completed'] ? 'completed' : 'pending';

            DB::table('tasks')->insert([
                'id' => $task['id'],
                'project_id' => $task['project_id'],
                'title' => $task['title'],
                'description' => $task['description'],
                'status' => $status,
                'due_date' => $task['due_date'],
                'assigned_to' => null, // Old CRM doesn't have assigned_to
                'sort_order' => $task['sort_order'] ?? 0,
                'created_at' => $task['created_at'],
                'updated_at' => $task['updated_at'],
            ]);
        }
        $this->command->info("Migrated " . count($tasks) . " tasks");

        // 4. Migrate Payments
        $this->command->info('Migrating payments...');
        $payments = $oldDb->query("SELECT * FROM payments")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($payments as $payment) {
            // Get client_id from project
            $clientId = DB::table('projects')
                ->where('id', $payment['project_id'])
                ->value('client_id');

            DB::table('payments')->insert([
                'id' => $payment['id'],
                'entity_id' => $redstarId,
                'client_id' => $clientId,
                'project_id' => $payment['project_id'],
                'subscription_id' => null,
                'type' => 'full', // Old CRM doesn't distinguish types
                'amount' => $payment['amount'],
                'currency' => $payment['currency'] ?? 'EUR',
                'gateway' => null, // Old CRM was manual payments
                'gateway_payment_id' => null,
                'gateway_customer_id' => null,
                'status' => 'paid', // All old payments are completed
                'payment_link_token' => null,
                'link_expires_at' => null,
                'metadata' => json_encode([
                    'method' => $payment['method'] ?? 'manual',
                    'reference' => $payment['reference'],
                    'notes' => $payment['notes'],
                    'migrated_from_old_crm' => true,
                ]),
                'paid_at' => $payment['payment_date'], // Map payment_date to paid_at
                'refunded_at' => null,
                'failure_reason' => null,
                'created_at' => $payment['created_at'],
                'updated_at' => $payment['updated_at'],
            ]);
        }
        $this->command->info("Migrated " . count($payments) . " payments");

        // 5. Check for contacts table (optional)
        try {
            $contacts = $oldDb->query("SELECT * FROM contacts")->fetchAll(\PDO::FETCH_ASSOC);

            if (count($contacts) > 0) {
                $this->command->info('Migrating contacts...');

                foreach ($contacts as $contact) {
                    DB::table('contacts')->insert([
                        'id' => $contact['id'],
                        'client_id' => $contact['client_id'],
                        'name' => $contact['name'],
                        'email' => $contact['email'] ?? null,
                        'phone' => $contact['phone'] ?? null,
                        'position' => $contact['position'] ?? null,
                        'is_primary' => $contact['is_primary'] ?? false,
                        'created_at' => $contact['created_at'],
                        'updated_at' => $contact['updated_at'],
                    ]);
                }
                $this->command->info("Migrated " . count($contacts) . " contacts");
            }
        } catch (\PDOException $e) {
            $this->command->warn("No contacts table found in old CRM (skipping)");
        }

        // Summary
        $this->command->info("\n=== Migration Summary ===");
        $this->command->info("Clients: " . DB::table('clients')->count());
        $this->command->info("Projects: " . DB::table('projects')->count());
        $this->command->info("Tasks: " . DB::table('tasks')->count());
        $this->command->info("Payments: " . DB::table('payments')->count());

        try {
            $contactCount = DB::table('contacts')->count();
            $this->command->info("Contacts: " . $contactCount);
        } catch (\Exception $e) {
            // Contacts table doesn't exist
        }

        $this->command->info("\nMigration completed successfully!");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot safely reverse this migration
        // Old CRM data would need to be re-imported from backup
        $this->command->warn('This migration cannot be reversed automatically.');
        $this->command->warn('Restore from backup if needed.');
    }
};
