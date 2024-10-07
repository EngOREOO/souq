<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        // Load all the commands in the Commands directory
        $this->load(__DIR__.'/Commands');

        // Include console routes
        require base_path('routes/console.php');
    }
}
