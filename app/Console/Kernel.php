<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('wa:sync-templates')
            ->hourly()
            ->withoutOverlapping();

        $schedule->command('pelanggan:auto-isolate')
            ->dailyAt('00:30')
            ->withoutOverlapping();

        $schedule->command('tagihan:create')
            ->dailyAt('07:00')
            ->withoutOverlapping();

        $schedule->command('tagihan:send-wa')
            ->everyTenMinutes()
            ->withoutOverlapping();

        $schedule->command('mikrotik:sync-ppp')
            ->everyThirtyMinutes()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
