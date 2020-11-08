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
    private $_startFrom = 15;
    private $_duration = 5;
    
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
        $cvfilename = env('TORRENT_DOWNLOAD_DIRECTORY').DIRECTORY_SEPARATOR.time().'_cv.mp4';
        
        $cmd = 'ffmpeg -ss %s -i %s -to %s -c copy -copyts %s';
        $cmd = sprintf(
            $cmd,
            $this->_startFrom,
            $this->filepath,
            $this->_startFrom + $this->_duration,
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
        $sendToAddress = config('mail.to.address');
        $sendToName = config('mail.to.name');
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
