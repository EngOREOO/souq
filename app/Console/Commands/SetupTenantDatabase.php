<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use App\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;
use Exception;

class SetupTenantDatabase extends Command
{
    protected $signature = 'tenant:setup
                            {name : The name of the tenant}
                            {database : The name of the tenant database}';

    protected $description = 'Sets up a new tenant database and runs the necessary migrations';

    public function handle()
    {
        // Get the tenant name and database name from input
        $tenantName = $this->argument('name');
        $databaseName = $this->argument('database');

        // DB::beginTransaction();

        // try {
            // Step 1: Add new entry to tenants table in master database
            $tenant = Tenant::create([
                'name' => $tenantName,
                'database_name' => $databaseName
            ]);

            $this->info("Tenant {$tenantName} created successfully in the master database.");

            // Step 2: Create a new tenant-specific database
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}`;"); // Backticks for safety
            $this->info("Database {$databaseName} created successfully.");

            // Step 3: Create a domain for the tenant
            Domain::create([
                'domain' => "{$tenantName}.yourdomain.com", // Adjust as needed
                'tenant_id' => $tenant->id,
            ]);
            $this->info("Domain created successfully for tenant {$tenantName}.");

            // // Step 4: Dynamically switch to the tenant's database connection
            // Config::set('database.connections.tenant', [
            //     'driver'    => 'mysql',
            //     'host'      => env('DB_HOST', '127.0.0.1'),
            //     'port'      => env('DB_PORT', '3306'),
            //     'database'  => $databaseName,
            //     'username'  => env('DB_USERNAME', 'root'),
            //     'password'  => env('DB_PASSWORD', ''),
            //     'charset'   => 'utf8mb4',
            //     'collation' => 'utf8mb4_unicode_ci',
            //     'prefix'    => '',
            //     'strict'    => true,
            //     'engine'    => null,
            // ]);

            // DB::purge('tenant');
            // DB::reconnect('tenant');

            // Step 5: Run migrations for the tenant (create the products table)
            Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--database' => 'tenant',
                '--force' => true,
            ]);

            $this->info("Migrations run successfully for tenant {$tenantName}.");

            // Commit the transaction
            // DB::commit();

        // } catch (Exception $e) {
        //     // Rollback the transaction if something went wrong
        //     DB::rollBack();
        //     $this->error("Failed to set up tenant: " . $e->getMessage());
        //     return 1; // Indicate an error occurred
        // }

        return 0; // Success
    }
}
