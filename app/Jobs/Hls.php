<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Jobs\AwsUploader;

use App\Services\SourceFactory\DownloadFileRecordsService;

use App\Constants\Common;

class Hls implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $downloadedPath;
    
    protected $data;
    
    protected $hlsStorePath;
    
    protected $filename;
    
    protected $fullFilenamePath;
    
    protected $downloadFileRecordsService;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $downloadBasePath, array $data, DownloadFileRecordsService $downloadFileRecordsService)
    {
        $this->setDownloadedPath($downloadBasePath);
        
        $this->setData($data);
        
        $this->setFilename();
        
        $this->setHlsStorePath();
        
        $this->setFullFilenamePath();
        
        $this->downloadFileRecordsService = $downloadFileRecordsService;
    }

    private function setData(array $data = [])
    {
        $this->data = $data;
    }
    
    private function setDownloadedPath(string $downloadedPath = '')
    {
        $this->downloadedPath = $downloadedPath;
    }
    
    private function setHlsStorePath()
    {
        $this->hlsStorePath = $this->downloadedPath.DIRECTORY_SEPARATOR.'hls'.DIRECTORY_SEPARATOR.$this->filename;
    }
    
    private function setFilename()
    {
        $pathInfo = pathinfo($this->data['video'] ?? '');
        
        $this->filename = $pathInfo['filename'];
    }
    
    private function setFullFilenamePath()
    {
        $this->fullFilenamePath = $this->downloadedPath.DIRECTORY_SEPARATOR.$this->data['video'];
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (is_file($this->fullFilenamePath)) {
            if (!is_dir($this->hlsStorePath)) {
                mkdir($this->hlsStorePath, 0777, true);
            }
            
            $cmd = "ffmpeg -y -i {$this->fullFilenamePath} -codec:v libx264 -codec:a mp3 -map 0 -f ssegment -segment_format mpegts -segment_list {$this->hlsStorePath}".DIRECTORY_SEPARATOR."{$this->filename}.m3u8 -segment_time 10 {$this->hlsStorePath}".DIRECTORY_SEPARATOR."{$this->filename}_%03d.ts";
            
            $thumbnailArr = $this->getThumbnail();
            $thumbnail = $thumbnailArr['thumbnail'];
            $preview = $this->getVideoPreview()['preview'];
            $fullDuration = $thumbnailArr['fullDuration'];
            
            exec($cmd, $output, $return);
            
            if ($return == 0) {
                $this->setHlsCuttingDone($thumbnail, $preview);
                
                // Upload HLS to S3
                AwsUploader::dispatch($this->downloadedPath, $this->data, $this->downloadFileRecordsService, $fullDuration, $thumbnail, $preview);
            }
        }
    }
    
    private function doSubtitleMove()
    {
        if (file_exists($this->downloadedPath.DIRECTORY_SEPARATOR.$this->data['subtitle'])) {
            rename($this->downloadedPath.DIRECTORY_SEPARATOR.$this->data['subtitle'], $this->hlsStorePath.DIRECTORY_SEPARATOR.basename($this->data['subtitle']));
        }
    }
    
    private function setHlsCuttingDone(string $thumbnail, string $preview)
    {
        $this->downloadFileRecordsService->updateInfo([['id', '=', $this->data['id']]], [
            'thumbnail' => basename($thumbnail),
            'preview' => basename($preview),
            'status' => Common::HLS_DONE_CUTTING,
        ]);
        
        return true;
    }
    
    private function getDurationOfVideo()
    {
        $cmd = "ffmpeg -i {$this->fullFilenamePath} 2>&1 |grep 'Duration'";
        
        exec($cmd, $output);
        
        $durationMatchedArr = explode(',', $output[0] ?? []);
        
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
    
    private function getThumbnail()
    {
        $duration = $this->getDurationOfVideo();
        $durationSecondsInteger = $this->doDurationToSecondsInteger($duration);
        $durationHalfSecondsInteger = $durationSecondsInteger / 2;
        
        $thumbnailFilename = DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$this->filename.'_thumbnail.jpg';
        
        $duration = date('H:i:s', $durationHalfSecondsInteger);
        $durationArr = explode(':', $duration);
        
        $h = strval($durationArr[0]) ?? '00';
        $m = strval($durationArr[1]) ?? '00';
        $s = strval($durationArr[2]) ?? '00';
        
        $cmd = "ffmpeg -ss {$h}:{$m}:{$s} -y -i {$this->fullFilenamePath} -vframes 1 -q:v 2 {$thumbnailFilename}";
        
        exec($cmd);
        
        return [
            'thumbnail' => $thumbnailFilename,
            'fullDuration' => $duration,
        ];
    }
    
    private function getVideoPreview()
    {
        $duration = $this->getDurationOfVideo();
        $durationSecondsInteger = $this->doDurationToSecondsInteger($duration);
        $durationHalfSecondsInteger = $durationSecondsInteger / 2;
        
        $previewVideoFilename = $this->hlsStorePath.DIRECTORY_SEPARATOR.$this->filename.'_preview.mp4';
        
        $duration = date('H:i:s', $durationHalfSecondsInteger);
        $durationArr = explode(':', $duration);
        
        $h = strval($durationArr[0]) ?? '00';
        $m = strval($durationArr[1]) ?? '00';
        $s = strval($durationArr[2]) ?? '00';
        
        $cmd = "ffmpeg -ss {$h}:{$m}:{$s} -t 00:00:05 -y -i {$this->fullFilenamePath} -c:v libx264 -c:a aac -strict experimental -b:a 98k {$previewVideoFilename}";
        
        exec($cmd);
        
        return [
            'preview' => $previewVideoFilename,
            'fullDuration' => $duration,
        ];
    }
}
