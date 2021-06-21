<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\SourceFactory\DownloadFileRecordsService;

use App\Jobs\Hls;

use App\Constants\Common;

use App\Jobs\SendMail;

#use Illuminate\Support\Facades\Log;

class HlsCommand extends Command
{
    protected $downloadFileRecordsService;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hls:main';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Video cutting into HLS';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(DownloadFileRecordsService $downloadFileRecordsService)
    {
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
        $hlsMaking = $this->downloadFileRecordsService->getInfo([['status', '=', Common::HLS_IS_MAKING]], ['*'], ['id', 'DESC']);
        
        if ($hlsMaking) {
            dd('ID ('.$hlsMaking['id'].') Hls is making');
        }
        
        $data = $this->downloadFileRecordsService->getInfo([['status', '=', 0]], ['*'], ['id', 'DESC']);
        
        if (!$data) {
            SendMail::dispatch(1, 'Torrent Download Notice', [
                'body' => 'It`s time to download torrent ^_^',
            ]);
            
            dd('There is no data need to make hls, mail has been sent !');
        }
        
        $hlsMaking = $this->downloadFileRecordsService->updateInfo([['id', '=', $data['id']]], [
            'status' => Common::HLS_IS_MAKING
        ]);
        
        $downloadedPath = env('TORRENT_DOWNLOAD_DIRECTORY');
        
        Hls::dispatch($downloadedPath, $data, $this->downloadFileRecordsService);
    }
}
