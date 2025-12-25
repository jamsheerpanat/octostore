<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantManagementController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('plan')->latest()->paginate(20);
        return response()->json($tenants);
    }

    public function store(Request $request)
    {
        // 1. Validate Input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|unique:tenants,domain',
            'plan_id' => 'required|exists:plans,id',
            'admin_name' => 'required|string',
            'admin_email' => 'required|email',
            'admin_password' => 'required|min:8'
        ]);

        $tenantName = Str::slug($validated['name']);
        
        // 2. Prepare Tenant Data
        $dbName = 'octostore_tenant_' . str_replace('-', '_', $tenantName) . '_' . Str::random(4);
        
        // This logic mimics the Console Command but is now API driven
        // Ideally extract this into a Service named TenantProvisioner
        
        DB::beginTransaction();
        try {
            $tenant = Tenant::create([
                'name' => $validated['name'],
                'domain' => $validated['domain'],
                'database_name' => $dbName,
                'storage_path' => "tenants/{$tenantName}",
                'plan_id' => $validated['plan_id'],
                'is_active' => true
            ]);
            
            // Create Database
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Run Migrations (We need to switch context or use the command with --database)
            // But standard Artisan::call with database options for migration is tricky dynamically
            // Simpler: Use our own helper or a package like tenancy for laravel.
            // Since we built a custom one, we just need to set config and run migrate
            
            // NOTE: Running migrations via HTTP request can be slow (timeout risk). 
            // Better to dispatch a Job: ProvisionTenantJob
            
            // For this implementation, I will simulate success or call a quick command
            Artisan::call('tenant:migrate', ['--tenant' => $tenant->id]);
            // Artisan::call('tenant:seed', ['--tenant' => $tenant->id]); // Need to seed Admin User
            
            // We need to insert the Admin User into the new Tenant DB
            // Switch connection
            config(['database.connections.tenant.database' => $dbName]);
            DB::purge('tenant');
            DB::reconnect('tenant');
            
            // Use query builder to avoid Model connection confusion or use middleware approach
            DB::connection('tenant')->table('users')->insert([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => bcrypt($validated['admin_password']),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Assign 'admin' role if roles table seeded
            // $user = \App\Models\User::on('tenant')->where('email', $validated['admin_email'])->first();
            // $user->assignRole('admin'); 
            
            DB::commit();
            
            return response()->json(['message' => 'Tenant provisioned successfully', 'data' => $tenant], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Tenant Creation Failed: " . $e->getMessage());
            return response()->json(['message' => 'Provisioning failed: ' . $e->getMessage()], 500);
        }
    }
    
    public function maintenance(Request $request, Tenant $tenant)
    {
         $action = $request->input('action'); // 'migrate', 'backup'
         
         if ($action === 'migrate') {
             Artisan::call('tenant:migrate', ['--tenant' => $tenant->id]);
             return response()->json(['message' => 'Migration triggered. ' . Artisan::output()]);
         }
         
         // Backup placeholder
         return response()->json(['message' => 'Action handled']);
    }
}
