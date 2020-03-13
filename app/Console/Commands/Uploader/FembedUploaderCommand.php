<?php

namespace App\Console\Commands\Uploader;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Common\Fembed;
use App\Constants\Common;

use App\Services\SourceFactory\DownloadFilesService;

class FembedUploaderCommand extends Command
{
    protected $fembed;
    
    protected $downloadFilesService;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fembed:upload {info}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download information writting locally';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        Fembed $fembed,
        DownloadFilesService $downloadFilesService
        )
    {
        $this->fembed = $fembed;
        
        $this->downloadFilesService = $downloadFilesService;
        
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $parameter = $this->argument('info');
        
        $parsedParameters = $this->fembed->parseParameters($parameter);
        
        if ($parsedParameters === false) {
            return false;
        }
        
        Log::info('Download_finish_callback_parameter', ['parameters' => $parsedParameters]);
        
        $filepath = $parsedParameters['torrent_downloaded_file_name'];
        
        if (file_exists($filepath)) {
            $downloadFileRecord = $this->downloadFilesService->getInfo([], ['id'], ['id', 'DESC']);
            
            $this->downloadFilesService->updateInfo([
                ['id', '=', $downloadFileRecord['id']]
            ], [
                'download_finish' => Common::IS_DOWNLOAD_FINISHED,
                'filename' => $filepath,
            ]);
        }
    }
}
