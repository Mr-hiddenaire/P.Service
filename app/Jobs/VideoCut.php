<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class VideoCut implements ShouldQueue
{
    private $_duration_hour_start = '00';
    private $_duration_min_start = '00';
    private $_duration_sec_start = '00';
    
    private $_duration_hour_end = '00';
    private $_duration_min_end = '00';
    private $_duration_sec_end = '05';
    
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    
    protected $filepath;
    
    protected $type;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $filepath)
    {
        $this->data = $data;
        
        $this->filepath = $filepath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->doCV();
    }
    
    private function doCV()
    {
        $cvfilename = dirname($this->filepath).DIRECTORY_SEPARATOR.time().'_cv.mp4';
        
        $cmd = 'ffmpeg -ss %s:%s:%s -i %s -t %s:%s:%s -c copy %s';
        $cmd = sprintf(
            $cmd,
            $this->_duration_hour_start,
            $this->_duration_min_start,
            $this->_duration_sec_start,
            $this->filepath,
            $this->_duration_hour_end,
            $this->_duration_min_end,
            $this->_duration_sec_end,
            $cvfilename
            );
        
        Log::info('cv', ['filename' => $cmd]);
        
        exec($cmd);
        
        if (file_exists($cvfilename)) {
            $this->sendMail($cvfilename);
        }
    }
    
    private function sendMail($cvfilename)
    {
        $sendToAddress = env('SEND_TO_ADDRESS');
        $sendToName = env('SEND_TO_NAME');
        $sendTitle = 'SEO SUPPLIES';
        $sendBody = 'FILES NEEDED BY SEO';
        $attachmentPath = $cvfilename;
        
        $data = [
            'name' => $this->data['name'],
            'unique_id' => $this->data['unique_id'],
            'tags' => $this->data['tags'],
        ];
        
        Log::info('data', ['data' => $data]);
        
        Mail::send('emails.cv', $data, function($message) use ($sendToAddress, $sendToName, $sendTitle, $attachmentPath) {
            $message->to($sendToAddress, $sendToName)
            ->subject($sendTitle)
            ->attach($attachmentPath);
        });
        
    }
}
