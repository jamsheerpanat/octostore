<?php

namespace App\Tenancy\Commands;

use App\Tenancy\Models\Tenant;
use Illuminate\Console\Command;

class MigrateTenants extends Command
{
    protected $signature = 'tenant:migrate';

    protected $description = 'Run migrations for all active tenants';

    public function handle()
    {
        $tenants = Tenant::where('is_active', true)->get();

        foreach ($tenants as $tenant) {
            $this->info("Migrating tenant: {$tenant->name} ({$tenant->domain})");

            $tenant->configure();

            $this->call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        }
        
        $this->info("All tenants migrated.");
    }
}
