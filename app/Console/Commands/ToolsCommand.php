<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\OriginalSource\ContentsService;
use App\Services\SourceFactory\DownloadFilesService;
use App\Common\Transmission;
use App\Common\Fembed;

use App\Constants\Common;

use App\Jobs\VideoCut;

use App\Tools\FembedUploader;
use Spatie\Sitemap\SitemapGenerator;

class ToolsCommand extends Command
{
    protected $contentsService;
    
    protected $downloadFilesService;
    
    protected $transmission;
    
    protected $fembed;
    
    protected $fembedUploader;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tool:cmd 
        {--method=}
        {--original-id=}
        {--archive-priority=}
        {--data=}
        {--filepath=}
        {--sh=}
        {--sm=}
        {--ss=}
        {--eh=}
        {--em=}
        {--es=}
        {--video-path=}
        {--thumbnail=}
        {--video_id=}
        {--downloaded_file_record_id=}
        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tools';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ContentsService $contentsService,
        DownloadFilesService $downloadFilesService,
        Transmission $transmission,
        Fembed $fembed,
        FembedUploader $fembedUploader
        )
    {
        $this->contentsService = $contentsService;
        
        $this->downloadFilesService = $downloadFilesService;
        
        $this->transmission = $transmission;
        
        $this->fembed = $fembed;
        
        $this->fembed->doAccountSetting();
        
        $this->fembedUploader = new FembedUploader();
        
        $this->fembedUploader->SetAccount(config('fembed.account'));
        
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $method = $this->option('method');
        
        if (!$method) {
            dd('No method supplied');
        }
        
        $this->{$method}();
    }
    
    private function getJsonContent()
    {
        $originalId = intval($this->option('original-id'));
        
        if (!$originalId) {
            dd('Original id required');
        }
        
        $res = $this->contentsService->getInfo([
            ['id', '=', $originalId]
        ], ['*'], ['id', 'DESC']);
        
        echo json_encode($res).PHP_EOL;
    }
    
    private function doArchive()
    {
        $originalId = intval($this->option('original-id'));
        $archivePriority = intval($this->option('archive-priority'));
        
        if (!$originalId) {
            dd('Original id required');
        }
        
        if (!$archivePriority) {
            dd('Archive priority value required');
        }
        
        echo 'Archiving ...'.PHP_EOL;
        
        $uResult = $this->contentsService->modify([
            ['id', '=', $originalId]
        ], [
            'is_archive' => 1,
            'archive_priority' => $archivePriority,
        ]);
        
        if ($uResult) {
            echo 'Archive successfully !'.PHP_EOL;
        } else {
            echo 'Archive fail !'.PHP_EOL;
        }
        
        echo 'Deleting downloaded file ...'.PHP_EOL;
        
        $dResult = $this->downloadFilesService->deleteInfo(['original_source_id' => $originalId]);
        
        if ($dResult) {
            echo 'Delete download file successfully !'.PHP_EOL;
        } else {
            echo 'Delete download file fail !'.PHP_EOL;
        }
        
        @$this->transmission->doRemoveForce();
    }
    
    private function reupload()
    {
        $downloadedFileInfo = $this->downloadFilesService->getInfo([
            ['download_finish', '=', Common::IS_DOWNLOAD_FINISHED]
        ], ['*'], ['id', 'DESC']);
        
        $filepath = env('TORRENT_DOWNLOAD_DIRECTORY').DIRECTORY_SEPARATOR.$downloadedFileInfo['filename'];
        
        if (is_dir($filepath)) {
            $this->fembed->doMultiFilesUpload($filepath, $downloadedFileInfo);
        } else if (is_file($filepath)) {
            $this->fembed->doSingleFileUpload($filepath, $downloadedFileInfo);
        }
    }
    
    private function testMail()
    {
        $data = json_decode($this->option('data'), true);
        $filepath = $this->option('filepath');
        
        if (!$data) {
            dd('Data required');
        }
        
        if (!$filepath) {
            dd('Filepath required');
        }
        
        VideoCut::dispatch($data, $filepath)->onConnection('redis')->onQueue('seo.cv.queue');
    }
    
    private function testSitemap()
    {
        SitemapGenerator::create(config('app.url'))->writeToFile(public_path('sitemap.xml'));
    }
    
    private function vc()
    {
        $filepath = $this->option('filepath');
        $sh = $this->option('sh');
        $sm = $this->option('sm');
        $ss = $this->option('ss');
        
        $eh = $this->option('eh');
        $em = $this->option('em');
        $es = $this->option('es');
        
        if (!$filepath) {
            dd('Filepath required');
        }
        
        if (!$sh) {
            dd('sh required');
        }
        
        if (!$sm) {
            dd('sm required');
        }
        
        if (!$ss) {
            dd('ss required');
        }
        
        if (!$eh) {
            dd('eh required');
        }
        
        if (!$em) {
            dd('em required');
        }
        
        if (!$es) {
            dd('es required');
        }
        
        VideoCut::dispatchNow([
            'name' => 'tool',
            'unique_id' => 'tool',
            'tags' => 'tool',
        ], $filepath, $sh, $sm, $ss, $eh, $em, $es);
    }
    
    private function ffmpeg()
    {
        $videoPath = $this->option('video-path');
        
        if (!$videoPath) {
            dd('Video Path required for ffmpeg');
        }
        
        VideoCut::dispatchNow([], $videoPath);
    }
    
    private function uploadThumbnail()
    {
        $thumbnailPath = $this->option('thumbnail') ?? '';
        $videoId = $this->option('video_id') ?? '';
        
        if (!$thumbnailPath) {
            dd('Thumbnail Path required for Fembed Uploader for video poster');
        }
        
        if (!$videoId) {
            dd('Video Id required for Fembed Uploader for video poster');
        }
        
        $res = $this->fembedUploader->doThumbnailUpload($thumbnailPath, $videoId);
        
        dd($res);
    }
    
    private function doFileGroup()
    {
        $result = [];
        
        $str = '84d2f48082dfcb802bee268d9a93b040.avi, a9bfba9fb43af0ffb1f0780254e85eb3.avi';
        #$str = 'a9bfba9fb43af0ffb1f0780254e85eb3.avi, 0561edaf067bcd3ab4862cb16e5b7893.vtt';
        #$str = '84d2f48082dfcb802bee268d9a93b040.avi, a9bfba9fb43af0ffb1f0780254e85eb3.avi,0561edaf067bcd3ab4862cb16e5b7893.vtt';
        
        $files = explode(',', $str);
        
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
        
        var_dump($result);
    }
    
    private function doAWSupload()
    {
        $downloadedFileRecordId = $this->option('downloaded_file_record_id') ?? 0;
        
        if (!$downloadedFileRecordId) {
            dd('Downloaded File Record Id Required');
        }
        
        $downloadedFileRecordService = new \App\Services\SourceFactory\DownloadFileRecordsService(new \App\Model\SourceFactory\DownloadFileRecordsModel());
        
        $info = $downloadedFileRecordService->getInfo([['id', '=', $downloadedFileRecordId]], ['*'], ['id', 'DESC']);
        
        $downloadedPath = env('TORRENT_DOWNLOAD_DIRECTORY');
        
        \App\Jobs\AwsUploader::dispatch($downloadedPath, $info, $downloadedFileRecordService);
    }
}
