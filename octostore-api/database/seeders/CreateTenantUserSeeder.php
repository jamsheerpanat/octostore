<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateTenantUserSeeder extends Seeder
{
    public function run(): void
    {
        // Must be run in tenant context
        $user = User::create([
            'name' => 'Owner User',
            'email' => 'owner@megastore.com',
            'password' => Hash::make('password'),
        ]);

        $user->assignRole('Owner');
    }
}
