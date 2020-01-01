<?php

namespace App\Console\Commands\Uploader;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Common\Fembed;
use App\Constants\Common;

use App\Services\SourceFactory\AsyncLogService;
use App\Services\SourceFactory\ContentsService;
use App\Common\Transmission;

class FembedUploaderCommand extends Command
{
    protected $fembed;
    
    protected $asyncLogService;
    
    protected $contentsService;
    
    protected $transmission;
    
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
    protected $description = 'Upload files to [FEMBED]';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Fembed $fembed, AsyncLogService $asyncLogService, ContentsService $contentsService, Transmission $transmission)
    {
        $this->fembed = $fembed;
        
        $this->fembed->doAccountSetting();
        
        $this->asyncLogService = $asyncLogService;
        
        $this->contentsService = $contentsService;
        
        $this->transmission = $transmission;
        
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
        
        Log::info('Original parameter: '.$parameter);
        
        $parsedParameters = $this->fembed->parseParameters($parameter);
        
        if ($parsedParameters === false) {
            return false;
        }
        
        Log::info('Download_finish_callback_parameter: '.json_encode($parsedParameters));
        
        $filepath = $parsedParameters['torrent_download_dir'].DIRECTORY_SEPARATOR.$parsedParameters['torrent_downloaded_file_name'];
        
        if (file_exists($filepath)) {
            Log::info($filepath.' file exists');
            
            if (is_dir($filepath)) {
                $res = $this->fembed->dealWithDirectory($filepath);
            } else {
                $res = $this->fembed->dealWithFile($filepath);
            }
            
            if ($res->result == 'success') {
                $this->addContents($res);
                $this->clearFiles($filepath);
                $this->transmission->doRemove();
            }
            
            return $res;
            
        } else {
            Log::info($filepath.' file not exists');
            
            return false;
        }
    }
    
    /**
     * TODO optimize the code .to define an interface and then do an implementation
     * @param object $res
     * @return boolean
     */
    private function addContents($res)
    {
        $asyncLog = $this->asyncLogService->getInfo([], ['*'], ['id', 'ASC']);
        
        $asyncLogContent = json_decode($asyncLog['content'], true);
        
        $this->asyncLogService->updateAsyncLog([['id', '=', $asyncLog['id']]], ['status' => Common::ASYNC_LOG_HANDLE_FINISH]);
        
        // In order to reupload when there is someting wrong after uploading.
        $contents = $this->contentsService->getInfo([['origin_source_id', '=', $asyncLogContent['id']]], ['id'], ['id', 'ASC']);
        
        if ($contents) {
            $this->contentsService->deleteInfo([['origin_source_id', '=', $asyncLogContent['id']]]);
        }
        
        $this->contentsService->addContents([
            'name' => $asyncLogContent['name'],
            'unique_id' => $asyncLogContent['unique_id'],
            'tags' => $asyncLogContent['tags'],
            'type' => $asyncLogContent['type'],
            'thumb_url' => $asyncLogContent['thumb_url'],
            'video_url' => $res->data,
            'origin_source_id' => $asyncLogContent['id'],
        ]);
        
        return true;
    }
    
    private function clearFiles($filepath)
    {
        if (is_dir($filepath)) {
            $this->doDirDel($filepath, true);
        } else {
            $this->doDirDel(env('TORRENT_DOWNLOAD_DIRECTORY'), false);
        }
        
        $this->doDirDel(env('TORRENT_WATCH_DIRECTORY'), false);
        $this->doDirDel(env('TORRENT_RESUME_DIRECTORY'), false);
        $this->doDirDel(env('TORRENT_TORRENT_DIRECTORY'), false);
    }
    
    private function doDirDel($path, $selfDeletion = false)
    {
        $path = $path.DIRECTORY_SEPARATOR;
        
        if (! is_dir($path)) {
            throw new \InvalidArgumentException($path.'must be a directory');
        }
        
        if (substr($path, strlen($path) - 1, 1) != DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }
        
        $files = glob($path.'*', GLOB_MARK);
        
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->doDirDel($file);
            } else {
                Log::info('Deleting the file '.$file);
                
                $res = unlink($file);
                
                if ($res) {
                    Log::info($file.' deletion success');
                } else {
                    Log::info($file.' deletion fail');
                }
            }
        }
        
        if ($selfDeletion) {
            Log::info('Deleting the directory '.$path);
            
            $res = rmdir($path);
            
            if ($res) {
                Log::info($path.' deletion success');
            } else {
                Log::info($path.' deletion fail');
            }
        }
    }
}
