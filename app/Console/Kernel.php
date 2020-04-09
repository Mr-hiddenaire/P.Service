<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\Uploader\FembedUploaderCommand::class,
        \App\Console\Commands\Torrent\ServiceCommand::class,
        \App\Console\Commands\Uploader\VUploaderCommand::class,
        \App\Console\Commands\ToolsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        ### Download resources ###
        $schedule->command('command:torrent:download')->everyMinute();
        
        ### Upload videos ###
        $schedule->command('do:video:upload')->everyMinute();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
