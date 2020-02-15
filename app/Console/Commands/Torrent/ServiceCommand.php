<?php

namespace App\Console\Commands\Torrent;

use Illuminate\Console\Command;

use App\Services\OriginalSource\ContentsService;
use App\Services\SourceFactory\AsyncLogService;

use App\Constants\Common;
use App\Tools\ImagesUploader;

#use Illuminate\Support\Facades\Log;

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
            $rawSource = $this->pickUpOneItem($asyncLog);
            
            if ($rawSource) {
                $this->asyncLogService->addAsyncLog(['content' => json_encode($rawSource), 'status' => Common::ASYNC_LOG_NOT_HANDLE]);
            }
        } else {
            if ($asyncLog['status'] == Common::ASYNC_LOG_HANDLE_FINISH) {
                $rawSource = $this->pickUpOneItem($asyncLog);
                
                if ($rawSource) {
                    $this->asyncLogService->updateAsyncLog([['id', '=', $asyncLog['id']]], ['content' => json_encode($rawSource), 'status' => Common::ASYNC_LOG_NOT_HANDLE]);
                }
            }
        }
        
        return true;
    }
    
    private function pickUpOneItem($asyncLog)
    {
        $rawSource = $this->getOneRawSource($asyncLog);
        
        if ($rawSource) {
            $rawSource = $this->reformatRawData($rawSource);
            $this->tDownload($rawSource);
            $rawSource = $this->thumbReset($rawSource);
        }
        
        return $rawSource;
    }
    
    private function tDownload($rawSource)
    {
        $resourceHandler = fopen($rawSource['torrent_url'], 'rb');
        
        $targetHandler = fopen(env('TORRENT_WATCH_DIRECTORY').DIRECTORY_SEPARATOR.pathinfo($rawSource['torrent_url'])['filename'].'.torrent', 'wb');
        
        if ($resourceHandler === false || $targetHandler === false) {
            
            throw new \Exception('Cant open file');
        }
        
        while (!feof($resourceHandler)) {
            if (fwrite($targetHandler, fread($resourceHandler, 1024)) === false) {
                
                throw new \Exception('Cant write file');
            }
        }
        
        fclose($resourceHandler);
        
        fclose($targetHandler);
        
        return true;
    }
    
    private function reformatRawData(array $data)
    {
        // all type of source should be reformated, but for asia type, images can be used directly.and for euro type, images should be reformated cause of hotlink forbidden
        switch ($data['type']) {
            case Common::IS_AISA:
                $data['torrent_url'] = env('P_SCRAWLER_URL').'/torrent/'.$data['torrent_url'];
                break;
            case Common::IS_EURO:
                $data['thumb_url'] = env('P_SCRAWLER_URL').'/images/'.$data['thumb_url'];
                $data['torrent_url'] = env('P_SCRAWLER_URL').'/torrent/'.$data['torrent_url'];
                break;
        }
        
        return $data;
    }
    
    private function thumbReset(array $data)
    {
        // Euro`s image can not be hotlink.so upload to free hosting.
        if (isset($data['type']) && $data['type'] == Common::IS_EURO) {
            if (isset($data['thumb_url']) && $data['thumb_url']) {
                $ibbRes = $this->imagesUploader->Ibb($data['thumb_url']);
                
                if (isset($ibbRes['thumb_url']) && $ibbRes['thumb_url']) {
                    $data['thumb_url'] = $ibbRes['thumb_url'];
                }
            }
        }
        
        return $data;
    }
    
    private function getOneRawSource($asyncLog)
    {
        $rdmn = $this->randType();
        
        $where = [['pick_up_status', '=', Common::IS_NOT_PICKED_UP], ['type', '=', $rdmn]];
        
        $data = $this->contentsService->getInfo($where, ['*'], ['id', 'ASC']);
        
        if ($data) {
            $this->contentsService->modify([['id', '=', $data['id']]], ['pick_up_status' => Common::IS_PICKED_UP, 'pick_up_time' => time()]);
        }
        
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
