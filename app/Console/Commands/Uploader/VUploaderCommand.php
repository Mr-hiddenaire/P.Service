<?php

namespace App\Console\Commands\Uploader;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Log;
use App\Constants\Common;
use App\Common\Fembed;

use App\Services\SourceFactory\DownloadFilesService;

class VUploaderCommand extends Command
{
    protected $fembed;
    
    protected $downloadFilesService;
    
    protected $transmission;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'do:video:upload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start upload the video to [FEMBED]';

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
        
        $this->fembed->doAccountSetting();
        
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
        $downloadedFileInfo = $this->downloadFilesService->getInfo([
            ['download_finish', '=', Common::IS_DOWNLOAD_FINISHED]
        ], ['*'], ['id', 'DESC']);
        
        if ($downloadedFileInfo && $downloadedFileInfo['status'] != Common::IS_UPOADING && $downloadedFileInfo['filename']) {
            // Set uploading
            $this->downloadFilesService->updateInfo([
                ['id', '=', $downloadedFileInfo['id']]
            ], [
                'status' => Common::IS_UPOADING,
            ]);
            
            $filepath = env('TORRENT_DOWNLOAD_DIRECTORY').DIRECTORY_SEPARATOR.$downloadedFileInfo['filename'];
            
            if (is_dir($filepath)) {
                $this->fembed->doMultiFilesUpload($filepath, $downloadedFileInfo);
            } else if (is_file($filepath)) {
                $this->fembed->doSingleFileUpload($filepath, $downloadedFileInfo);
            }
        } else {
            Log::info('File not finished download yet or uploading now');
        }
    }
}
