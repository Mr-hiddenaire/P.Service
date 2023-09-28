<?php

namespace App\Console\Commands;

use Exception;
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
    protected $signature = 'torrent:downloader:download {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve data from db and then download torrent';

    /**
     * Create a new command instance.
     *
     * @param ContentsService $contentsService
     * @param DownloadFilesService $downloadFilesService
     * @param ImagesUploader $imagesUploader
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
     * @throws Exception
     */
    public function handle()
    {
        $type = $this->option('type') ?? 0;
        
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
        
        $originalSource = $this->pickUpOneItem($type);
        
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
    private function pickUpOneItem($type)
    {
        $rawSource = $this->getOneRawSource($type);
        
        if ($rawSource) {
            $rawSource = $this->reformatRawData($rawSource);
        }
        
        return $rawSource;
    }
    
    /**
     * Download torrent from self server
     * @param array $rawSource
     * @throws Exception
     * @return boolean
     */
    private function tDownload($rawSource)
    {
        $resourceHandler = fopen($rawSource['torrent_url'], 'rb');
        
        $targetHandler = fopen(env('TORRENT_WATCH_DIRECTORY').DIRECTORY_SEPARATOR.pathinfo(urlencode($rawSource['torrent_path']))['filename'].'.torrent', 'wb');
        
        if ($resourceHandler === false || $targetHandler === false) {
            
            throw new Exception('Cant open file');
        }
        
        while (!feof($resourceHandler)) {
            if (fwrite($targetHandler, fread($resourceHandler, 1024)) === false) {
                
                throw new Exception('Cant write file');
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
     * Get original source
     * @param $type
     * @return array
     */
    private function getOneRawSource($type)
    {
        if ($type) {
            $rdmn = $type;
        } else {
            $rdmn = $this->randType();
        }
        
        $where = [['pick_up_status', '=', Common::IS_NOT_PICKED_UP], ['type', '=', $rdmn], ['is_scraped', '=', Common::SCRAPED_FINISH]];
        
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
        return rand(Common::IS_AISA, Common::IS_EURO);
    }
}
