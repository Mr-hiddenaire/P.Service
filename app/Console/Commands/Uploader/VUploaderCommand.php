<?php

namespace App\Console\Commands\Uploader;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Common\Fembed;
use App\Constants\Common;

use App\Services\SourceFactory\ContentsService;
use App\Services\SourceFactory\DownloadFilesService;

use App\Common\Transmission;

class VUploaderCommand extends Command
{
    protected $fembed;
    
    protected $asyncLogService;
    
    protected $contentsService;
    
    protected $transmission;
    
    protected $downloadFilesService;
    
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
    public function __construct(
        Fembed $fembed,
        ContentsService $contentsService,
        Transmission $transmission,
        DownloadFilesService $downloadFilesService
        )
    {
        $this->fembed = $fembed;
        
        $this->fembed->doAccountSetting();
        
        $this->contentsService = $contentsService;
        
        $this->transmission = $transmission;
        
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
        
    }
}
