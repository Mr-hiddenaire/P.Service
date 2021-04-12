<?php

namespace App\Common;

use App\Tools\FembedUploader;
use Illuminate\Support\Facades\Log;

use App\Services\SourceFactory\ContentsService;
use App\Services\SourceFactory\DownloadFilesService;

use App\Common\Transmission;

use App\Constants\Common;

use App\Jobs\VideoCut;

class Fembed extends FembedUploader
{
    protected $contentsService;
    
    protected $downloadFilesService;
    
    protected $transmission;
    
    const VIDEO_FORMAT = [
        'mkv', 'wmv', 'avi', 'mp4', 'mpeg4', 'mpegps', 'flv', '3gp', 'webm', 'mov', 'mpg', 'm4v',
    ];
    
   public function __construct(
       ContentsService $contentsService,
       DownloadFilesService $downloadFilesService,
       Transmission $transmission
       )
   {
       $this->contentsService = $contentsService;
       
       $this->transmission = $transmission;
       
       $this->downloadFilesService = $downloadFilesService;
       
       parent::__construct();
   }
   
   /**
    * Upload single file
    * @param string $filepath
    * @return \stdClass
    */
   public function doSingleFileUpload($filepath, ...$parameters)
   {
       $downloadedFileInfo = $parameters[0];
       $originalSource = json_decode($downloadedFileInfo['original_source_info'], true);
       
       $this->doFileSetting($filepath);
       
       $res = $this->Run();
       
       Log::info('Single: uploaded result to fembed', ['result' => $res]);
       
       if ($res->result == 'success') {
           $data = [
               'name' => $originalSource['name'],
               'unique_id' => $originalSource['unique_id'],
               'tags' => $originalSource['tags'],
               'type' => $originalSource['type'],
               'thumb_url' => $originalSource['thumb_url'],
               'video_url' => $res->data,
               'origin_source_id' => $originalSource['id'],
               'is_sync_status' => Common::IS_NOT_SYNC,
           ];
       
           VideoCut::dispatchNow($data, $filepath);
           
           // Step first: add contents
           $this->contentsService->addContents($data);
           
           // Step second: local file deletion
           // TODO
           //unlink($filepath);
           
           // Step third: download info deletion
           $this->downloadFilesService->deleteInfo([
               ['id', '=', $downloadedFileInfo['id']]
           ]);
           
           // Step forth: transmission reload
           $this->transmission->doRemove();
           
           // Step fivth: clear transmission cache files
           // TODO
           //$this->clearFiles();
       }
   }
   
   /**
    * Upload multi files under the directory
    * @param string $filepath
    * @return \stdClass
    */
   public function doMultiFilesUpload($filepath, ...$parameters)
   {
       $counter = 1;
       
       $downloadedFileInfo = $parameters[0];
       $originalSource = json_decode($downloadedFileInfo['original_source_info'], true);
       
       $directory = new \RecursiveDirectoryIterator($filepath);
       
       foreach (new \RecursiveIteratorIterator($directory) as $filename => $file) {
           $extension = pathinfo($filename, PATHINFO_EXTENSION);
           
           if (in_array($extension, self::VIDEO_FORMAT)) {
               
               $this->doFileSetting($filename);
               
               $res = $this->Run();
               
               if ($res->result == 'success') {
                   $data = [
                       'name' => $originalSource['name'].' part('.$counter.')',
                       'unique_id' => $originalSource['unique_id'],
                       'tags' => $originalSource['tags'],
                       'type' => $originalSource['type'],
                       'thumb_url' => $originalSource['thumb_url'],
                       'video_url' => $res->data,
                       'origin_source_id' => $originalSource['id'],
                       'is_sync_status' => Common::IS_NOT_SYNC,
                   ];
                   
                   VideoCut::dispatchNow($data, $filename);
                   
                   // Step first: add contents
                   $this->contentsService->addContents($data);
                   
                   // Step second: local file deletion
                   // TODO
                   //unlink($filename);
                   
                   Log::info('Multi: uploaded result to fembed('.$counter.')', ['result' => $res]);
                   
                   $counter = $counter + 1;
               }
           } else {
               $realFilename = basename($filename);
               if ($realFilename != '.' && $realFilename != '..') {
                   // other file deletion directly
                   // TODO
                   //unlink($filename);
               }
           }
       }
       
       // Step fivth: delete the directory come up with downloaded file
       if (file_exists($filepath)) {
           if (is_dir($filepath)) {
               // TODO
               //rmdir($filepath);
           }
       }
       
       // TODO
       // Make sure upload successfully.[here implies all files uploaded successfully.]
       /*
       if (!file_exists($filepath)) {
           // Step third: download info deletion
           $this->downloadFilesService->deleteInfo([
               ['id', '=', $downloadedFileInfo['id']]
           ]);
           
           // Step forth: transmission reload
           $this->transmission->doRemove();
           
           // Step sixth: clear transmission cache files
           $this->clearFiles();
       }
       */
       
       // Step third: download info deletion
       $this->downloadFilesService->deleteInfo([
           ['id', '=', $downloadedFileInfo['id']]
       ]);
       
       // Step forth: transmission reload
       $this->transmission->doRemove();
   }
   
   private function clearFiles()
   {
       rrmdir(env('TORRENT_DOWNLOAD_DIRECTORY'));
       rrmdir(env('TORRENT_WATCH_DIRECTORY'));
       rrmdir(env('TORRENT_RESUME_DIRECTORY'));
       rrmdir(env('TORRENT_TORRENT_DIRECTORY'));
   }
   
   /**
    * Parameters parser
    * @param string $parameters
    * @return array
    */
   public function parseParameters(string $parameters)
   {
       $result = [];
       
       if (!$parameters) {
           return false;
       }
       
       $parameterArr = explode('@@@', $parameters);
       
       $torrentAppVersion = $parameterArr[0];
       $torrentDownloadedFileLocaltime = $parameterArr[1];
       $torrentDownloadDir = $parameterArr[2];
       $torrentDownloadedFileHash = $parameterArr[3];
       $torrentDownloadedFileId = $parameterArr[4];
       $torrentDownloadedFileName = $parameterArr[5];
       
       if (!$torrentAppVersion
           || !$torrentDownloadedFileLocaltime
           || !$torrentDownloadDir
           || !$torrentDownloadedFileHash
           || !$torrentDownloadedFileId
           || !$torrentDownloadedFileName
           ) {
           return false;
       }
       
       $result = [
           'torrent_app_version' => $torrentAppVersion,
           'torrent_downloaded_file_localtime' => $torrentDownloadedFileLocaltime,
           'torrent_download_dir' => $torrentDownloadDir,
           'torrent_downloaded_file_hash' => $torrentDownloadedFileHash,
           'torrent_downloaded_file_id' => $torrentDownloadedFileId,
           'torrent_downloaded_file_name' => $torrentDownloadedFileName,
       ];
       
       return $result;
   }
   
   /**
    * Set file
    * @param string $file
    */
   public function doFileSetting($file)
   {
       $this->SetInput($file);
   }
   
   /**
    * Set fembed account
    * @param array $account
    */
   public function doAccountSetting($account = [])
   {
       if (!$account) {
           $account = $this->_getFembedAccount();
       }
       
       $this->SetAccount($account);
   }
   
   /**
    * Get fembed account information
    * @throws \Exception
    * @return StdClass
    */
   private function _getFembedAccount()
   {
       $account = config('fembed.account');
       
       if (!$account) {
           throw new \Exception('Fembed account not config yet !');
       }
       
       return (object) $account;
   }
}
