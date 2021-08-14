<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Log;
use App\Constants\Common;

use App\Services\SourceFactory\DownloadFilesService;

class VideoUploaderCommand extends Command
{
    protected $downloadFilesService;
    
    protected $validUploader = ['aws', 'fembed'];
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:uploader:upload {uploader_type?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start upload the video to [FEMBED]';

    /**
     * Create a new command instance.
     *
     * @param DownloadFilesService $downloadFilesService
     */
    public function __construct(DownloadFilesService $downloadFilesService)
    {
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
        $uploaderType = $this->argument('uploader_type') ?? 'aws';
        $uploaderType = strtolower($uploaderType);
        
        if (!in_array($uploaderType, $this->validUploader)) {
            dd('Only support uploader type '.implode(' | ', $this->validUploader).' .');
        }
        
        switch ($uploaderType) {
            case 'aws':
                $this->doAWSUpload();
                break;
            case 'fembed':
                $this->doFEMBEDUpload();
                break;
            default:
                break;
        }
    }
    
    private function doAWSUpload()
    {
        
    }
    
    /**
     * @deprecated
     */
    private function doFEMBEDUpload()
    {
        $downloadedFileInfo = $this->downloadFilesService->getInfo([
            ['download_finish', '=', Common::IS_DOWNLOAD_FINISHED]
        ], ['*'], ['id', 'DESC']);
        
        if ($downloadedFileInfo && $downloadedFileInfo['status'] != Common::IS_UPLOADING && $downloadedFileInfo['filename']) {
            // Set uploading
            $this->downloadFilesService->updateInfo([
                ['id', '=', $downloadedFileInfo['id']]
            ], [
                'status' => Common::IS_UPLOADING,
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
