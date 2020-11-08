<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\OriginalSource\ContentsService;
use App\Services\SourceFactory\DownloadFilesService;
use App\Common\Transmission;
use App\Common\Fembed;

use App\Constants\Common;

use App\Jobs\VideoCut;

use Spatie\Sitemap\SitemapGenerator;

class ToolsCommand extends Command
{
    protected $contentsService;
    
    protected $downloadFilesService;
    
    protected $transmission;
    
    protected $fembed;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tool:cmd {--method=} {--original-id=} {--archive-priority=} {--data=} {--filepath=}';

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
        Fembed $fembed
        )
    {
        $this->contentsService = $contentsService;
        
        $this->downloadFilesService = $downloadFilesService;
        
        $this->transmission = $transmission;
        
        $this->fembed = $fembed;
        
        $this->fembed->doAccountSetting();
        
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
        
        if (!$filepath) {
            dd('Filepath required');
        }
        
        VideoCut::dispatch([], $filepath)->onConnection('redis')->onQueue('seo.cv.queue');
    }
}
