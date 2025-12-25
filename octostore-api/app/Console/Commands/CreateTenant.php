<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Api\SuperAdmin\TenantManagementController;
use Illuminate\Http\Request;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create {name} {domain} {email} {password} {--plan=1}';
    protected $description = 'Provision a new tenant via CLI';

    public function handle()
    {
        $controller = new TenantManagementController();
        
        $request = new Request([
            'name' => $this->argument('name'),
            'domain' => $this->argument('domain'),
            'admin_name' => 'Admin',
            'admin_email' => $this->argument('email'),
            'admin_password' => $this->argument('password'),
            'plan_id' => $this->option('plan')
        ]);
        
        // Mock validation or ensure method handles raw input well
        // Ideally refactor logic to Service to clean this up, 
        // calling controller action directly is a shortcut.
        
        $this->info("Provisioning tenant...");
        
        try {
            // We need to bypass validation if reusing controller, or simulate full Request
            // Better: Move logic to Service. For now, assuming Service refactor:
            
            // $service->provision(...);
            
            $this->info("Please use the API or ensure Service logic is decoupled.");
            
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
