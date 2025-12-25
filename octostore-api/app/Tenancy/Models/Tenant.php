<?php

namespace App\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Tenant extends Model
{
    protected $connection = 'mysql'; // Always use master DB

    protected $guarded = [];

    /**
     * Configure the system to use this tenant.
     */
    public function configure(): void
    {
        // Clone the master 'mysql' connection
        $config = Config::get('database.connections.mysql');

        // Override with tenant specific details
        $config['database'] = $this->database_name;

        Config::set('database.connections.tenant', $config);

        DB::purge('tenant');
        DB::reconnect('tenant');

        // Set as default connection
        Config::set('database.default', 'tenant');
    }
}
