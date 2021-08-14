<?php

namespace App\Console;

use App\Console\Commands\AwsSignerCommand;
use App\Console\Commands\HlsCommand;
use App\Console\Commands\SitemapCommand;
use App\Console\Commands\ToolsCommand;
use App\Console\Commands\TorrentDownloaderCommand;
use App\Console\Commands\TransmissionCallbackCommand;
use App\Console\Commands\VideoUploaderCommand;
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
        TransmissionCallbackCommand::class,
        TorrentDownloaderCommand::class,
        VideoUploaderCommand::class,
        ToolsCommand::class,
        SitemapCommand::class,
        AwsSignerCommand::class,
        HlsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        
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
