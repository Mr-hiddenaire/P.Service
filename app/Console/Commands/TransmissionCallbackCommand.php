<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\SourceFactory\DownloadFilesService;
use App\Services\SourceFactory\DownloadFileRecordsService;

use Illuminate\Support\Facades\Log;

class TransmissionCallbackCommand extends Command
{
    protected $downloadFilesService;
    
    protected $downloadFileRecordsService;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transmission:callback:main {--files=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Callback here when transmission download done';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(DownloadFilesService $downloadFilesService, DownloadFileRecordsService $downloadFileRecordsService)
    {
        $this->downloadFilesService = $downloadFilesService;
        
        $this->downloadFileRecordsService = $downloadFileRecordsService;
        
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $files = $this->option('files') ?? '';
        
        Log::info('Transmission callback parameters', ['parameters' => $files]);
        
        $downloadedFiles = $this->downloadFilesService->getInfo([], ['*'], ['id', 'DESC']);
        
        $parsedFiles = $this->doFilesParse($files);
        $insertedData = $this->getInsertedData($parsedFiles, $downloadedFiles);
        
        $res = $this->downloadFileRecordsService->addInfo($insertedData);
        
        return $res;
    }
    
    private function getInsertedData(array $parsedFiles, array $downloadedFiles)
    {
        $insertedData = [];
        
        foreach ($parsedFiles['videos'] as $videoFile) {
            if (isset($parsedFiles['subtitle']) && $parsedFiles['subtitle']) {
                $subtitleFile = $parsedFiles['subtitle'];
            } else {
                $subtitleFile = '';
            }
            
            $info = [
                'original_source_id' => $downloadedFiles['original_source_id'],
                'video' => $videoFile,
                'subtitle' => $subtitleFile,
                'thumbnail' => '',
                'preview' => '',
                'status' => 0,
            ];
            
            $insertedData[] = $info;
        }
        
        return $insertedData;
    }
    
    private function doFilesParse(string $downloadedFiles)
    {
        $files = explode(',', $downloadedFiles);
        
        // Filter out video and subtitle file
        // Only one pair
        if (count($files) == 2) {
            $haveSubtitle = false;
            
            foreach ($files as $file) {
                $pathInfo = pathinfo($file);
                $fileExtension = $pathInfo['extension'];
                $subtitleFormats = config('formats.subtitle');
                if (in_array($fileExtension, $subtitleFormats)) {
                    $haveSubtitle = true;
                }
            }
            
            if ($haveSubtitle) {
                foreach ($files as $file) {
                    $pathInfo = pathinfo($file);
                    $fileExtension = $pathInfo['extension'];
                    $videoFormats = config('formats.video');
                    
                    if (in_array($fileExtension, $videoFormats)) {
                        $result['videos'][] = $file;
                    }
                }
                
                foreach ($files as $file) {
                    $pathInfo = pathinfo($file);
                    $fileExtension = $pathInfo['extension'];
                    $subtitleFormats = config('formats.subtitle');
                    
                    if (in_array($fileExtension, $subtitleFormats)) {
                        $result['subtitle'] = $file;
                    }
                }
            } else {
                // Filter out all video files and drop all subtitle files
                foreach ($files as $file) {
                    $pathInfo = pathinfo($file);
                    $fileExtension = $pathInfo['extension'];
                    $videoFormats = config('formats.video');
                    
                    if (in_array($fileExtension, $videoFormats)) {
                        $result['videos'][] = $file;
                    }
                }
                
                $result['subtitle'] = [];
            }
        } else {
            // Filter out all video files and drop all subtitle files
            foreach ($files as $file) {
                $pathInfo = pathinfo($file);
                $fileExtension = $pathInfo['extension'];
                $videoFormats = config('formats.video');
                
                if (in_array($fileExtension, $videoFormats)) {
                    $result['videos'][] = $file;
                }
            }
            
            $result['subtitle'] = [];
        }
        
        return $result;
    }
}
