<?php

namespace App\Tenancy\Commands;

use App\Tenancy\Models\Tenant;
use Illuminate\Console\Command;

class SeedTenant extends Command
{
    protected $signature = 'tenant:seed {name} {--class=DatabaseSeeder}';

    protected $description = 'Seed a tenant database';

    public function handle()
    {
        $name = $this->argument('name');
        $class = $this->option('class');

        $tenant = Tenant::where('name', $name)->first();

        if (!$tenant) {
            $this->error("Tenant {$name} not found.");
            return;
        }

        $this->info("Seeding tenant: {$tenant->name}");
        $tenant->configure();

        $this->call('db:seed', [
            '--class' => $class,
            '--database' => 'tenant',
            '--force' => true,
        ]);
        
        $this->info("Done.");
    }
}
