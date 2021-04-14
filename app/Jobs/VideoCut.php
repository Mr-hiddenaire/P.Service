<?php

namespace App\Jobs;

use App\Tools\FembedUploader;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
#use Illuminate\Support\Facades\Log;

class VideoCut implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fembedUploader;
    
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
        
        $this->filepath = doSpecialFilenameReformat($filepath);
        
        $this->fembedUploader = new FembedUploader();
        
        $this->fembedUploader->SetAccount(config('fembed.account'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->doItemsPickOutViaFFMPEG();
    }
    
    private function doItemsPickOutViaFFMPEG()
    {
        $duration = $this->getDurationOfVideo($this->filepath);
        $previewVideoFilename = $this->getVideoPreview($this->filepath, $duration);
        $thumbnailFilename = $this->getThumbnail($this->filepath, $duration);
        
        $this->data['duration'] = $duration;
        $this->data['previewVideoFilename'] = $previewVideoFilename;
        $this->data['thumbnailFilename'] = $thumbnailFilename;
        
        // Upload thumbnail to Fembed for specific video id
        $this->fembedUploader->doThumbnailUpload($thumbnailFilename, $this->data['video_url']);
        
        $this->sendMail();
        
        if (file_exists($previewVideoFilename)) {
            // TODO
            //unlink($previewVideoFilename);
        }
        
        if (file_exists($thumbnailFilename)) {
            // TODO
            //unlink($thumbnailFilename);
        }
    }
    
    private function getVideoPreview(string $filename, string $duration)
    {
        $durationSecondsInteger = $this->doDurationToSecondsInteger($duration);
        $durationHalfSecondsInteger = $durationSecondsInteger / 2;
        
        $previewVideoFilename = env('TORRENT_DOWNLOAD_DIRECTORY').DIRECTORY_SEPARATOR.md5($filename).'_preview.mp4';
        
        $duration = date('H:i:s', $durationHalfSecondsInteger);
        $durationArr = explode(':', $duration);
        
        $h = strval($durationArr[0]) ?? '00';
        $m = strval($durationArr[1]) ?? '00';
        $s = strval($durationArr[2]) ?? '00';
        
        $cmd = "ffmpeg -ss {$h}:{$m}:{$s} -t 00:00:05 -i {$filename} -c:v libx264 -c:a aac -strict experimental -b:a 98k {$previewVideoFilename}";
        
        exec($cmd);
        
        return $previewVideoFilename;
    }
    
    private function getThumbnail(string $filename, string $duration)
    {
        $durationSecondsInteger = $this->doDurationToSecondsInteger($duration);
        $durationHalfSecondsInteger = $durationSecondsInteger / 2;
        
        $thumbnailFilename = env('TORRENT_DOWNLOAD_DIRECTORY').DIRECTORY_SEPARATOR.md5($filename).'_thumbnail.jpg';
        
        $duration = date('H:i:s', $durationHalfSecondsInteger);
        $durationArr = explode(':', $duration);
        
        $h = strval($durationArr[0]) ?? '00';
        $m = strval($durationArr[1]) ?? '00';
        $s = strval($durationArr[2]) ?? '00';
        
        $cmd = "ffmpeg -ss {$h}:{$m}:{$s} -i {$filename} -vframes 1 -q:v 2 {$thumbnailFilename}";
        
        exec($cmd);
        
        return $thumbnailFilename;
    }
    
    private function getDurationOfVideo(string $filename)
    {
        $cmd = "ffmpeg -i {$filename} 2>&1 |grep 'Duration'";
        
        exec($cmd, $output);
        
        $durationMatchedArr = explode(',', $output[0]?? []);
        
        $durationMatchedRes = $durationMatchedArr[0] ?? '';
        
        preg_match('/\d+:\d+:\d+/', $durationMatchedRes, $duration);
        
        return $duration[0] ?? '';
    }
    
    private function doDurationToSecondsInteger(string $duration)
    {
        $secondsInteger = 0;
        
        $secondsArr = explode(':', $duration);
        
        $h = intval($secondsArr[0]) ?? 0;
        $m = intval($secondsArr[1]) ?? 0;
        $s = intval($secondsArr[2]) ?? 0;
        
        $h = $h*3600;
        $m = $m*60;
        
        $secondsInteger = $h + $m + $s;
        
        return $secondsInteger;
    }
    
    private function sendMail()
    {
        $sendToAddress = config('mail.to.address');
        $sendToName = config('mail.to.name');
        $sendTitle = 'Release Contents';
        $sendBody = 'Release Contents';
        
        $data = $this->data;
        
        Mail::send('emails.cv', $this->data, function($message) use ($sendToAddress, $sendToName, $sendTitle, $data) {
            $message->to($sendToAddress, $sendToName)
            ->subject($sendTitle)
            ->attach($data['previewVideoFilename'])
            ->attach($data['thumbnailFilename']);
        });
    }
}
