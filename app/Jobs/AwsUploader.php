<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\SourceFactory\DownloadFileRecordsService;

use Aws\S3\S3Client;

use App\Constants\Common;

use App\Jobs\SendMail;

class AwsUploader implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $downloadedPath;
    
    protected $data;
    
    protected $hlsStorePath;
    
    protected $filename;
    
    protected $fullFilenamePath;
    
    protected $downloadFileRecordsService;
    
    protected $fullDuration;
    
    protected $thumbnail;
    
    protected $preview;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $downloadBasePath, array $data, DownloadFileRecordsService $downloadFileRecordsService, string $fullDuration, string $thumbnail, string $preview)
    {
        $this->downloadFileRecordsService = $downloadFileRecordsService;
        
        $this->setDownloadedPath($downloadBasePath);
        
        $this->setData($data);
        
        $this->setFilename();
        
        $this->setHlsStorePath();
        
        $this->fullDuration = $fullDuration;
        
        $this->thumbnail = $thumbnail;
        
        $this->preview = $preview;
    }

    private function setDownloadedPath(string $downloadedPath = '')
    {
        $this->downloadedPath = $downloadedPath;
    }
    
    private function setData(array $data = [])
    {
        $refreshData = $this->downloadFileRecordsService->getInfo([['id', '=', $data['id']], ['status', '=', Common::HLS_DONE_CUTTING]], ['*'], ['id', 'DESC']);
        
        $this->data = $refreshData;
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
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $downloadFilesService = new \App\Services\SourceFactory\DownloadFilesService(new \App\Model\SourceFactory\DownloadFilesModel());
        $downloadFilesInfo = $downloadFilesService->getInfo([['original_source_id', '=', $this->data['original_source_id']]], ['*'], ['id', 'DESC']);
        $downloadFilesInfoArr = json_decode($downloadFilesInfo['original_source_info'], true);
        
        if (is_dir($this->hlsStorePath)) {
            $s3Client = new S3Client([
                'profile' => 'default',
                'region' => 'us-east-1',
                'version' => 'latest',
            ]);
            
            $s3UploadRes = $s3Client->uploadDirectory($this->hlsStorePath, 'dailyporns', 'hls-bundle/'.basename($this->hlsStorePath));
            $this->setHlsUploadDone();
            
            SendMail::dispatch(2, 'Hls files uploaded successfully', [
                'body' => 'All Hls files are uploaded successfully ^_^',
                'uniqueId' => $downloadFilesInfoArr['unique_id'],
                'name' => $downloadFilesInfoArr['name'],
                'tags' => $downloadFilesInfoArr['tags'],
                'duration' => $this->fullDuration,
                'thumbnail' => $this->thumbnail,
                'preview' => $this->preview,
                'preview_url' => env('CF_ENDPOINT').'/hls-bundle/'.$this->filename.'/'.basename($this->preview),
                'hls_url' => env('CF_ENDPOINT').'/hls-bundle/'.$this->filename.'/'.$this->filename.'.m3u8',
            ]);
            
            $this->doOriginalFileDeletion();
            $this->doHlsDeletion();
            $this->doThumbnailDeletion();
        }
    }
    
    private function setHlsUploadDone()
    {
        $this->downloadFileRecordsService->updateInfo([['id', '=', $this->data['id']]], [
            'status' => Common::HLS_DONE_UPLOAD,
        ]);
        
        return true;
    }
    
    private function doThumbnailDeletion()
    {
        if (file_exists($this->thumbnail)) {
            unlink($this->thumbnail);
        }
    }
    
    private function doOriginalFileDeletion()
    {
        $originalFilePath = $this->downloadedPath.DIRECTORY_SEPARATOR.$this->data['video'];
        
        if (file_exists($originalFilePath)) {
            unlink($originalFilePath);
        }
    }
    
    private function doHlsDeletion()
    {
        $hlsFilePath = $this->downloadedPath.DIRECTORY_SEPARATOR.'hls'.DIRECTORY_SEPARATOR.$this->filename;
        
        rrmdir($hlsFilePath);
        
        $handle = opendir($hlsFilePath);
        closedir($handle);
        rmdir($hlsFilePath);
    }
}
