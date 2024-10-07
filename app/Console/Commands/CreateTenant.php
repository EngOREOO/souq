<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create {tenantName} {dbName}';
    protected $description = 'Create a new tenant and its corresponding database';

    public function handle()
    {
        $tenantName = $this->argument('tenantName');
        $dbName = $this->argument('dbName');

        // Create the database
        $this->createDatabase($dbName);

        // Prepare the tenant data
        $tenantData = [
            'name' => $tenantName,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'database_name' => $dbName,
            'tenancy_db_name' => $this->generateTenancyDbName(),
        ];

        // Insert the tenant data into the tenants table
        $tenantId = DB::table('tenants')->insertGetId([
            'id'    => Str::random(16),
            'data' => json_encode($tenantData)
        ]);

        // Migrate the tenant database
        $this->migrateTenantDatabase($dbName);

        $this->info("Tenant '{$tenantName}' with database '{$dbName}' created successfully.");

        $domain = Domain::create([
            'domain'    => $tenantName,
            'tenant_id' => $tenantId
        ]);
    }

    protected function createDatabase($dbName)
    {
        $connection = config('database.connections.mysql');

        try {
            $pdo = new \PDO(
                "mysql:host={$connection['host']};charset=utf8",
                $connection['username'],
                $connection['password']
            );

            // Check if the database already exists
            $databases = $pdo->query("SHOW DATABASES LIKE '{$dbName}'")->fetchAll();
            if (count($databases) > 0) {
                $this->error("Database '{$dbName}' already exists.");
                return;
            }

            // Create the database
            $pdo->exec("CREATE DATABASE `{$dbName}`");
            $this->info("Database '{$dbName}' created successfully.");
        } catch (\PDOException $e) {
            $this->error("Error creating database: " . $e->getMessage());
        }
    }

    protected function migrateTenantDatabase($dbName)
    {
        // Set the database connection for the tenant
        config(['database.connections.tenant' => [
            'driver' => 'mysql',
            'host' => config('database.connections.mysql.host'),
            'port' => config('database.connections.mysql.port'),
            'database' => $dbName,
            'username' => config('database.connections.mysql.username'),
            'password' => config('database.connections.mysql.password'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]]);

        // Run the migrations for the tenant from the defined path in your config
        $migrationParams = config('tenancy.migration_parameters');
        $migrationParams['--database'] = 'tenant';

        Artisan::call('migrate', $migrationParams);

        $this->info("Tenant database '{$dbName}' migrated successfully.");
    }

    protected function generateTenancyDbName()
    {
        // Generate a unique tenancy DB name, you can customize this as needed
        return 'tenant' . (string) Str::uuid();
    }
}
