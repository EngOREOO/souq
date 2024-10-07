<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant; // Assuming you have a Tenant model

class SwitchTenantDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Retrieve tenant_id from the request (header or authenticated user)
        $tenantId = $request->header('X-Tenant-ID') ?? auth()->user()->tenant_id ?? null;

        if ($tenantId) {
            // Fetch the tenant's database details from the 'tenants' table
            $tenant = Tenant::where('id', $tenantId)->first();

            if ($tenant && $tenant->database_name) {
                // Set the tenant's database connection dynamically
                Config::set('database.connections.tenant', [
                    'driver'    => 'mysql',
                    'host'      => env('DB_HOST', '127.0.0.1'),
                    'port'      => env('DB_PORT', '3306'),
                    'database'  => $tenant->database_name,
                    'username'  => env('DB_USERNAME', 'root'),
                    'password'  => env('DB_PASSWORD', ''),
                    'charset'   => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix'    => '',
                    'strict'    => true,
                    'engine'    => null,
                ]);

                // Set the new connection to be the active one
                DB::purge('tenant');
                DB::reconnect('tenant');

                // Optional: Check if the connection and tables exist
                try {
                    Schema::connection('tenant')->getConnection()->reconnect();
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Could not connect to tenant database'], 500);
                }
            } else {
                return response()->json(['error' => 'Tenant not found or database not configured'], 404);
            }
        } else {
            return response()->json(['error' => 'Tenant ID is required'], 400);
        }

        return $next($request);
    }
}
