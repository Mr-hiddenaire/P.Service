<?php

namespace App\Jobs;

use App\Model\SourceFactory\DownloadFilesModel;
use App\Services\SourceFactory\DownloadFilesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\SourceFactory\DownloadFileRecordsService;

use Aws\S3\S3Client;

use App\Constants\Common;

class AwsUploader implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $downloadedPath;
    
    protected $data;
    
    protected $hlsStorePath;
    
    protected $filename;
    
    protected $downloadFileRecordsService;

    /**
     * Create a new job instance.
     *
     * @param string $downloadBasePath
     * @param array $data
     * @param DownloadFileRecordsService $downloadFileRecordsService
     */
    public function __construct(string $downloadBasePath, array $data, DownloadFileRecordsService $downloadFileRecordsService)
    {
        $this->downloadFileRecordsService = $downloadFileRecordsService;
        
        $this->setDownloadedPath($downloadBasePath);
        
        $this->setData($data);
        
        $this->setFilename();
        
        $this->setHlsStorePath();
    }

    private function setDownloadedPath(string $downloadedPath = '')
    {
        $this->downloadedPath = $downloadedPath;
    }
    
    private function setData(array $data = [])
    {
        $this->data = $data;
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
        $downloadFilesService = new DownloadFilesService(new DownloadFilesModel());
        $downloadFilesInfo = $downloadFilesService->getInfo([['original_source_id', '=', $this->data['original_source_id']]], ['*'], ['id', 'DESC']);
        $downloadFilesInfoArr = json_decode($downloadFilesInfo['original_source_info'], true);
        
        if (is_dir($this->hlsStorePath)) {
            $s3Client = new S3Client([
                'profile' => 'default',
                'region' => 'us-east-1',
                'version' => 'latest',
            ]);

            $s3Client->uploadDirectory($this->hlsStorePath, 'dailyporns', 'hls-bundle/'.basename($this->hlsStorePath));
            $this->setHlsUploadDone();
            
            if (is_file($this->downloadedPath.DIRECTORY_SEPARATOR.$this->data['subtitle'])) {
                $subtitle = env('CF_ENDPOINT').'/hls-bundle/'.$this->filename.'/'.$this->data['subtitle'];
            } else {
                $subtitle = 'None';
            }
            
            SendMail::dispatch(2, 'Hls files uploaded successfully', [
                'body' => 'All Hls files are uploaded successfully ^_^',
                'uniqueId' => $downloadFilesInfoArr['unique_id'],
                'name' => $downloadFilesInfoArr['name'],
                'tags' => $downloadFilesInfoArr['tags'],
                'duration' => $this->data['fullDuration'],
                'thumbnail' => $this->data['thumbnail'],
                'preview' => $this->data['preview'],
                'preview_url' => env('CF_ENDPOINT').'/hls-bundle/'.$this->filename.'/'.basename($this->data['preview']),
                'hls_url' => env('CF_ENDPOINT').'/hls-bundle/'.$this->filename.'/'.$this->filename.'.m3u8',
                'subtitle' => $subtitle,
            ]);
            
            $this->doOriginalFileDeletion();
            $this->doHlsDeletion();
            $this->doThumbnailDeletion();
            
            // Specify downloaded files all done upload. so the specify download file deletion available
            $downloadedFileRecords = $this->downloadFileRecordsService->getAll([['original_source_id', '=', $this->data['original_source_id']]], ['*'], ['id', 'DESC']);
            $downloadedFileRecordsStatus = array_unique(array_column($downloadedFileRecords, 'status'));
            
            if (count($downloadedFileRecordsStatus) == 1 && $downloadedFileRecordsStatus[0] == Common::HLS_DONE_UPLOAD) {
                $downloadFilesService->updateInfo([['original_source_id', '=', $this->data['original_source_id']]], [
                    'status' => Common::DOWNLOAD_DELETION_ENABLE,
                ]);
            }
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
        if (file_exists($this->data['thumbnail'])) {
            unlink($this->data['thumbnail']);
        }
    }
    
    private function doOriginalFileDeletion()
    {
        $originalFilePathOfVideo = $this->downloadedPath.DIRECTORY_SEPARATOR.$this->data['video'];
        
        if (file_exists($originalFilePathOfVideo)) {
            unlink($originalFilePathOfVideo);
        }
        
        $originalFilePathOfSubtitle = $this->downloadedPath.DIRECTORY_SEPARATOR.$this->data['subtitle'];
        
        if (is_file($originalFilePathOfSubtitle)) {
            unlink($originalFilePathOfSubtitle);
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