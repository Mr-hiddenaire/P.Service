<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\OriginalSource\ContentsService;
use App\Services\SourceFactory\DownloadFilesService;
use App\Common\Transmission;

class ToolsCommand extends Command
{
    protected $contentsService;
    
    protected $downloadFilesService;
    
    protected $transmission;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tool:cmd {--method=} {--original-id=} {--archive-priority=}';

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
        Transmission $transmission
        )
    {
        $this->contentsService = $contentsService;
        
        $this->downloadFilesService = $downloadFilesService;
        
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
}
