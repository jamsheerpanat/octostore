<?php

namespace App\Tenancy\Commands;

use App\Tenancy\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create {name} {domain}';

    protected $description = 'Create a new tenant, database, and run migrations';

    public function handle()
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');

        // database name should be safe
        $dbName = 'tenant_' . Str::slug($name, '_') . '_' . Str::random(5);
        $storagePath = 'tenants/' . Str::slug($name);

        $this->info("Creating tenant {$name}...");

        // 1. Create Database
        // Note: The user running this must have CREATE DATABASE privileges
        try {
            DB::statement("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("Database {$dbName} created.");
        } catch (\Exception $e) {
            $this->error("Failed to create database: " . $e->getMessage());
            return 1;
        }

        // 2. Create Tenant Record
        $tenant = Tenant::create([
            'name' => $name,
            'domain' => $domain,
            'database_name' => $dbName,
            'storage_path' => $storagePath,
        ]);
        
        $this->info("Tenant created in Master DB.");

        // 3. Migrate Tenant DB
        $this->info("Migrating tenant database...");
        
        $tenant->configure();
        
        $this->call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant', 
            '--force' => true,
        ]);

        $this->info("Tenant {$name} setup complete!");
    }
}
