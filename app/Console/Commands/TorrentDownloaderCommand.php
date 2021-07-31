<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\OriginalSource\ContentsService;
use App\Services\SourceFactory\DownloadFilesService;

use App\Constants\Common;
use App\Tools\ImagesUploader;

use Illuminate\Support\Facades\Log;

class TorrentDownloaderCommand extends Command
{
    protected $contentsService;
    
    protected $downloadFilesService;
    
    protected $imagesUploader;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'torrent:downloader:download';

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
    public function __construct(
        ContentsService $contentsService,
        DownloadFilesService $downloadFilesService,
        ImagesUploader $imagesUploader
        )
    {
        parent::__construct();
        
        $this->contentsService = $contentsService;
        
        $this->downloadFilesService = $downloadFilesService;
        
        $this->imagesUploader = $imagesUploader;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $downloadInfo = $this->downloadFilesService->getInfo([], ['*'], ['id', 'DESC']);
        
        if ($downloadInfo) {
            if ($downloadInfo['status'] == Common::DOWNLOAD_DELETION_ENABLE) {
                $this->downloadFilesService->deleteInfo([
                    'id' => $downloadInfo['id']
                ]);
            } else {
                dd('Can download the torrent only after hls cut and upload');
            }
        }
        
        $originalSource = $this->pickUpOneItem();
        
        // random type maybe empty data
        if ($originalSource) {
            // thumb image upload first and then download torrent.sometimes, its fail during thumb uploading.
            $this->tDownload($originalSource);
            $this->setDownloadFileRecord($originalSource);
        }
    }
    
    private function setDownloadFileRecord(array $originalSource)
    {
        Log::info('Original source info before set', ['parameters' => $originalSource]);
        
        $data = [
            // Filename of file downloaded by transmission that will be set later. so predefine a empty value here.
            'filename' => '',
            'original_source_id' => $originalSource['id'],
            'download_finish' => Common::IS_DOWNLOAD_NOT_FINISHED_YET,
            'original_source_info' => json_encode($originalSource),
        ];
        
        return $this->downloadFilesService->addInfo($data);
    }
    
    /**
     * Pick up one item from original source library
     * @return array
     */
    private function pickUpOneItem()
    {
        $rawSource = $this->getOneRawSource();
        
        if ($rawSource) {
            $rawSource = $this->reformatRawData($rawSource);
        }
        
        return $rawSource;
    }
    
    /**
     * Download torrent from self server
     * @param array $rawSource
     * @throws \Exception
     * @return boolean
     */
    private function tDownload($rawSource)
    {
        $resourceHandler = fopen($rawSource['torrent_url'], 'rb');
        
        $targetHandler = fopen(env('TORRENT_WATCH_DIRECTORY').DIRECTORY_SEPARATOR.pathinfo($rawSource['torrent_path'])['filename'].'.torrent', 'wb');
        
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
    
    /**
     * Torrent url and thumb url generation
     * @param array $data
     * @return array
     */
    private function reformatRawData(array $data)
    {
        $data['torrent_url'] = env('P_CRAWLER_URL').'/torrent/'.$data['torrent_path'];
        
        return $data;
    }
    
    /**
     * Upload images to ibb for euro`s type source
     * @param array $data
     * @return array
     */
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
    
    /**
     * Get original source
     * @return array
     */
    private function getOneRawSource()
    {
        $rdmn = $this->randType();
        
        $where = [['pick_up_status', '=', Common::IS_NOT_PICKED_UP], ['type', '=', $rdmn], 'is_scraped', '=', Common::SCRAPED_FINISH];
        
        $data = $this->contentsService->getInfo($where, ['*'], ['id', 'ASC']);
        
        if ($data) {
            $this->contentsService->modify([['id', '=', $data['id']]], ['pick_up_status' => Common::IS_PICKED_UP, 'pick_up_time' => time()]);
        }
        
        return $data;
    }
    
    /**
     * Type generation random
     * @return number
     */
    private function randType()
    {
        $rdmn = rand(Common::IS_AISA, Common::IS_EURO);
        
        return $rdmn;
    }
}
