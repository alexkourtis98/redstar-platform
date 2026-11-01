<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\User;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create entities
        $redstar = Entity::create([
            'name' => 'RedStar Digital',
            'slug' => 'redstar',
            'domain' => 'redstarweb.net',
            'email' => 'info@redstarweb.net',
            'phone' => '+30 123 456 7890',
            'vat_number' => '999999999',
            'tax_office' => 'Athens',
            'primary_color' => '#DC2626',
            'is_active' => true,
        ]);

        $blueline = Entity::create([
            'name' => 'BlueLine Solutions',
            'slug' => 'blueline',
            'domain' => 'bluelinesolutions.net',
            'email' => 'info@bluelinesolutions.net',
            'primary_color' => '#2563EB',
            'is_active' => true,
        ]);

        $greenpromo = Entity::create([
            'name' => 'GreenPromo Marketing',
            'slug' => 'greenpromo',
            'domain' => 'greenpromo.net',
            'email' => 'info@greenpromo.net',
            'primary_color' => '#16A34A',
            'is_active' => true,
        ]);

        $amberlane = Entity::create([
            'name' => 'AmberLane Creative',
            'slug' => 'amberlane',
            'domain' => 'amberlane.net',
            'email' => 'info@amberlane.net',
            'primary_color' => '#F59E0B',
            'is_active' => true,
        ]);

        // Create super admin user
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@redstarweb.net',
            'password' => Hash::make('password'),
            'super_admin' => true,
            'last_entity_id' => $redstar->id,
        ]);

        // Attach admin to all entities
        $admin->entities()->attach($redstar->id, ['role' => 'admin', 'is_default' => true]);
        $admin->entities()->attach($blueline->id, ['role' => 'admin']);
        $admin->entities()->attach($greenpromo->id, ['role' => 'admin']);
        $admin->entities()->attach($amberlane->id, ['role' => 'admin']);

        // Create default expense categories
        $categories = [
            'Software & Subscriptions',
            'Hardware & Equipment',
            'Marketing & Advertising',
            'Travel & Accommodation',
            'Office Supplies',
            'Professional Services',
            'Utilities & Internet',
            'Training & Education',
        ];

        foreach ($categories as $category) {
            ExpenseCategory::create([
                'entity_id' => null, // Global category
                'name' => $category,
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        $this->command->info('Initial data seeded successfully!');
        $this->command->info('Login credentials:');
        $this->command->info('Email: admin@redstarweb.net');
        $this->command->info('Password: password');
    }
}
