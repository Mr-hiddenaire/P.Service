<?php

namespace App\Common;

use App\Tools\FembedUploader;
use Illuminate\Support\Facades\Log;

class Fembed extends FembedUploader
{
    const VIDEO_FORMAT = [
        'mkv', 'wmv', 'avi', 'mp4', 'mpeg4', 'mpegps', 'flv', '3gp', 'webm', 'mov', 'mpg', 'm4v',
    ];
    
   public function __construct()
   {
       parent::__construct();
   }
   
   public function dealWithFile($filepath)
   {
       $this->doFileSetting($filepath);
       
       $res = $this->Run();
       
       Log::info('Uploaded result to fembed: '.json_encode($res));
       
       return $res;
   }
   
   public function dealWithDirectory($filepath)
   {
       $directory = new \RecursiveDirectoryIterator($filepath);
       
       foreach (new \RecursiveIteratorIterator($directory) as $filename => $file) {
           $extension = pathinfo($filename, PATHINFO_EXTENSION);
           
           if (in_array($extension, self::VIDEO_FORMAT)) {
               
               Log::info('filename is (with directory):'.$filename.PHP_EOL);
               
               $this->doFileSetting($filename);
               
               $res = $this->Run();
               
               Log::info('Uploaded result to fembed: '.json_encode($res));
               
               return $res;
           }
       }
   }
   
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
       
       if (!$torrentAppVersion || !$torrentDownloadedFileLocaltime || !$torrentDownloadDir || !$torrentDownloadedFileHash || !$torrentDownloadedFileId || !$torrentDownloadedFileName) {
           
           Log::warning('Parameter error from shell script');
           
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
   
   public function doFileSetting($file)
   {
       $this->SetInput($file);
   }
   
   public function doAccountSetting($account = [])
   {
       if (!$account) {
           $account = $this->_getFembedAccount();
       }
       
       $this->SetAccount($account);
   }
   
   private function _getFembedAccount()
   {
       $account = config('fembed.account');
       
       if (!$account) {
           throw new \Exception('Fembed account not config yet !');
       }
       
       return (object) $account;
   }
}
