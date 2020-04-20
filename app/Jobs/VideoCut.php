<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Services\SourceFactory\DownloadFilesService;

use App\Constants\Common;

use App\Common\Fembed;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class VideoCut implements ShouldQueue
{
    private $_duration_hour_start = '00';
    private $_duration_min_start = '02';
    private $_duration_sec_start = '00';
    
    private $_duration_hour_end = '00';
    private $_duration_min_end = '00';
    private $_duration_sec_end = '30';
    
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $downloadFilesService;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(DownloadFilesService $downloadFilesService)
    {
        $this->downloadFilesService = $downloadFilesService;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /*
        $downloadedFileInfo = $this->downloadFilesService->getInfo([
            ['download_finish', '=', Common::IS_DOWNLOAD_FINISHED]
        ], ['*'], ['id', 'DESC']);
        
        $filepath = env('TORRENT_DOWNLOAD_DIRECTORY').DIRECTORY_SEPARATOR.$downloadedFileInfo['filename'];
        
        if (is_dir($filepath)) {
            $this->doVCD($filepath);
        } else if (is_file($filepath)) {
            $this->doVCF($filepath);
        }
        */
        
        $this->sendMail('/opt/htdocs/testCase/php/file2.php');
    }
    
    private function doVCF($filepath)
    {
        $cvfilename = dirname($filepath).DIRECTORY_SEPARATOR.time().'_cv.mp4';
        
        $cmd = 'ffmpeg -i %s -vcodec copy -acodec copy -ss %s:%s:%s -t %s:%s:%s %s';
        sprintf(
            $cmd,
            $filepath,
            $this->_duration_hour_start,
            $this->_duration_min_start,
            $this->_duration_sec_start,
            $this->_duration_hour_end,
            $this->_duration_min_end,
            $this->_duration_sec_end,
            $cvfilename
            );
        
        exec($cmd);
        
        if (file_exists($cvfilename)) {
            
        }
    }
    
    private function doVCD($filepath)
    {
        $directory = new \RecursiveDirectoryIterator($filepath);
        
        foreach (new \RecursiveIteratorIterator($directory) as $filename => $file) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array($extension, Fembed::VIDEO_FORMAT)) {
                $cvfilename = dirname($filename).DIRECTORY_SEPARATOR.time().'_cv.mp4';
                
                $cmd = 'ffmpeg -i %s -vcodec copy -acodec copy -ss %s:%s:%s -t %s:%s:%s %s';
                sprintf(
                    $cmd,
                    $filename,
                    $this->_duration_hour_start,
                    $this->_duration_min_start,
                    $this->_duration_sec_start,
                    $this->_duration_hour_end,
                    $this->_duration_min_end,
                    $this->_duration_sec_end,
                    $cvfilename
                    );
                
                exec($cmd);
                
                if (file_exists($cvfilename)) {
                    
                }
            }
        }
    }
    
    private function sendMail($filepath)
    {
        $sendToAddress = env('SEND_TO_ADDRESS');
        $sendToName = env('SEND_TO_NAME');
        $sendTitle = 'SEO SUPPLIES';
        $sendBody = 'FILES NEEDED BY SEO';
        $attachmentPath = $filepath;
        
        $data = ['body' => $sendBody];
        
        $newData = [
            'send_to_address' => $sendToAddress,
            'send_to_name' => $sendToName,
            'send_title' => $sendTitle,
            'send_body' => $sendBody,
            'attachment_path' => $attachmentPath,
        ];
        
        Log::info('data', ['body' => $newData]);
        
        Mail::send('emails.cv', $data, function($message) use ($sendToAddress, $sendToName, $sendTitle, $attachmentPath) {
            $message->to($sendToAddress, $sendToName)
            ->subject($sendTitle)
            ->attach($attachmentPath);
        });
        
    }
}
