<?php

namespace App\Console\Commands\Uploader;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Log;
use App\Constants\Common;
use App\Common\Fembed;

use App\Services\OriginalSource\ContentsService as OriginalContentsService;
use App\Services\SourceFactory\DownloadFilesService;

use App\Common\Transmission;

class VUploaderCommand extends Command
{
    protected $fembed;
    
    protected $originalContentsService;
    
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
        Transmission $transmission,
        DownloadFilesService $downloadFilesService,
        OriginalContentsService $originalContentsService
        )
    {
        $this->fembed = $fembed;
        
        $this->fembed->doAccountSetting();
        
        $this->transmission = $transmission;
        
        $this->downloadFilesService = $downloadFilesService;
        
        $this->originalContentsService = $originalContentsService;
        
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
        
        if ($downloadedFileInfo) {
            $originalSource = $this->originalContentsService->getInfo([
                ['id', '=', $downloadedFileInfo['original_source_id']]
            ], ['*'], ['id', 'DESC']);
            
            $filepath = env('TORRENT_DOWNLOAD_DIRECTORY').DIRECTORY_SEPARATOR.$downloadedFileInfo['filename'];
            
            if (file_exists($filepath)) {
                if (is_dir($filepath)) {
                    $this->fembed->doMultiFilesUpload($filepath, $originalSource);
                } else if (is_file($filepath)) {
                    $this->fembed->doSingleFileUpload($filepath, $originalSource);
                }
                
                $this->transmission->doRemove();
                
                $this->downloadFilesService->deleteInfo([
                    ['id', '=', $downloadedFileInfo['id']]
                ]);
                
                if (file_exists($filepath)) {
                    if (is_dir($filepath)) {
                        rmdir($filepath);
                    }
                }
            } else {
                Log::info('Downloaded file not found');
            }
        } else {
            Log::info('File not downloaded finished yet');
        }
    }
}
