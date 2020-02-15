<?php

namespace App\Console\Commands\Torrent;

use Illuminate\Console\Command;

use App\Services\OriginalSource\ContentsService;
use App\Services\SourceFactory\AsyncLogService;

use App\Constants\Common;
use App\Tools\ImagesUploader;

use Illuminate\Support\Facades\Log;

class ServiceCommand extends Command
{
    protected $contentsService;
    
    protected $asyncLogService;
    
    protected $imagesUploader;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:torrent:download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve data from db and then download torrent';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ContentsService $contentsService, AsyncLogService $asyncLogService, ImagesUploader $imagesUploader)
    {
        parent::__construct();
        
        $this->contentsService = $contentsService;
        
        $this->asyncLogService = $asyncLogService;
        
        $this->imagesUploader = $imagesUploader;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $asyncLog = $this->getAsyncLog();
        
        if (!$asyncLog) {
            $rawSource = $this->getOneRawSource($asyncLog);
            dd($rawSource);
            $tDownloadRes = $this->tDownload($rawSource);
            
            if (!$tDownloadRes) {
                Log::info('Torrent file download fail-1 ('.$rawSource['torrent_url'].')');
            }
            
            $rawSource = $this->thumbReset($rawSource);
            
            $this->asyncLogService->addAsyncLog(['content' => json_encode($rawSource), 'status' => Common::ASYNC_LOG_NOT_HANDLE]);
        } else {
            if ($asyncLog['status'] == Common::ASYNC_LOG_HANDLE_FINISH) {
                $rawSource = $this->getOneRawSource($asyncLog['content']);
                
                $tDownloadRes = $this->tDownload($rawSource);
                
                if (!$tDownloadRes) {
                    Log::info('Torrent file download fail-2 ('.$rawSource['torrent_url'].')');
                }
                
                $rawSource = $this->thumbReset($rawSource);
                $this->asyncLogService->updateAsyncLog([['id', '=', $asyncLog['id']]], ['content' => json_encode($rawSource), 'status' => Common::ASYNC_LOG_NOT_HANDLE]);
            }
        }
        
        return true;
    }
    
    private function tDownload($rawSource)
    {
        if (file_exists(env('STATISTICS_SCRAWLER_DIRECTORY').DIRECTORY_SEPARATOR.'torrent'.DIRECTORY_SEPARATOR.$rawSource['torrent_url'])) {
            $resourceHandler = fopen(env('STATISTICS_SCRAWLER_DIRECTORY').DIRECTORY_SEPARATOR.'torrent'.DIRECTORY_SEPARATOR.$rawSource['torrent_url'], 'rb');
            
            $targetHandler = fopen(env('TORRENT_WATCH_DIRECTORY').DIRECTORY_SEPARATOR.pathinfo($rawSource['torrent_url'])['filename'].'.torrent', 'wb');
            
            if ($resourceHandler === false || $targetHandler === false) {
                
                throw new \Exception('Cant open file');
            }
            
            while (!feof($resourceHandler)) {
                if (fwrite($targetHandler, fread($resourceHandler, 1024)) === false) {
                    
                    throw new \Exception('Cant write file');
                }
            }
            
            @unlink(env('STATISTICS_SCRAWLER_DIRECTORY').DIRECTORY_SEPARATOR.'torrent'.DIRECTORY_SEPARATOR.$rawSource['torrent_url']);
            
            fclose($resourceHandler);
            
            fclose($targetHandler);
            
            return true;
        }
        
        return false;
    }
    
    private function thumbReset(array $data)
    {
        // Euro`s image can not be hotlink.so upload to free hosting.
        if (isset($data['type']) && $data['type'] == Common::IS_EURO) {
            if (isset($data['thumb_url']) && $data['thumb_url']) {
                if (file_exists(env('STATISTICS_SCRAWLER_DIRECTORY').DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$data['thumb_url'])) {
                    $ibbRes = $this->imagesUploader->Ibb(env('STATISTICS_SCRAWLER_DIRECTORY').DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$data['thumb_url']);
                }
                
                if (isset($ibbRes['thumb_url']) && $ibbRes['thumb_url']) {
                    $data['thumb_url'] = $ibbRes['thumb_url'];
                    @unlink(env('STATISTICS_SCRAWLER_DIRECTORY').DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$data['thumb_url']);
                }
            }
        }
        
        return $data;
    }
    
    private function getOneRawSource($asyncLog)
    {
        $rdmn = $this->randType();
        
        if ($asyncLog) {
            $where = [['id', '>', $asyncLog['id']], ['type', '=', $rdmn]];
        } else {
            $where = [['id', '>', 0], ['type', '=', $rdmn]];
        }
        
        $data = $this->contentsService->getInfo($where, ['*'], ['id', 'ASC']);
        
        return $data;
    }
    
    private function getAsyncLog()
    {
        $result = [];
        
        $asyncLog = $this->asyncLogService->getInfo([], ['*'], ['id', 'ASC']);
        
        if ($asyncLog) {
            $result['id'] = $asyncLog['id'];
            $result['content'] = json_decode($asyncLog['content'], true);
            $result['status'] = $asyncLog['status'];
            return $result;
        }
        
        return $result;
    }
    
    private function randType()
    {
        $rdmn = rand(Common::IS_AISA, Common::IS_EURO);
        
        return $rdmn;
    }
}
