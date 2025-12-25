<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Permissions
        $permissions = [
            'manage_products',
            'manage_orders',
            'manage_customers',
            'manage_promotions',
            'manage_settings',
            'view_reports'
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // 2. Define Roles and Assign Permissions (Tenant Context)

        // Owner: All permissions
        $role = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        // Manager: All except settings?
        $role = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $role->syncPermissions(['manage_products', 'manage_orders', 'manage_customers', 'manage_promotions', 'view_reports']);

        // Staff: Products and Orders only
        $role = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
        $role->syncPermissions(['manage_products', 'manage_orders']);

        // Support: Customers and Orders only
        $role = Role::firstOrCreate(['name' => 'Support', 'guard_name' => 'web']);
        $role->syncPermissions(['manage_orders', 'manage_customers']);

        // Viewer: Reports only
        $role = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);
        $role->syncPermissions(['view_reports']);
    }
}
