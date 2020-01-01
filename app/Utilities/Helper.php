<?php

namespace App\Utilities;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class Helper
{
    public static function convertXMLToArray($xml)
    {
        $res = json_decode(json_encode(simplexml_load_string($xml)), true);
        
        return $res;
    }
    
    public static function createImageFromBase64($imgSource, $filename)
    {
        list($type, $imgSource) = explode(';', $imgSource);
        list(, $imgSource) = explode(',', $imgSource);
        
        if($imgSource != '') {
            Storage::disk('public')->put($filename,base64_decode($imgSource));
        }
    }
    
    public static function uploadFile(Request $request, $fileKey = 'thumb', $directory = 'images')
    {
        header("Access-Control-Allow-Origin: *");
        
        $result = [];
        
        if (is_array($request->file($fileKey))) {
            foreach ($request->file($fileKey) as $file) {
                if ($file) {
                    $result[] = env('APP_STATISTICS_URL').DIRECTORY_SEPARATOR.$file->store($directory, ['disk' => 'upload']);
                }
            }
        } else {
            if ($request->file($fileKey)) {
                $result[] = env('APP_STATISTICS_URL').DIRECTORY_SEPARATOR.$request->file($fileKey)->store($directory, ['disk' => 'upload']);
            }
        }
        
        return $result;
    }
    
    public static function alert($msg, $isExit = false)
    {
        if ($isExit) {
            echo '<script type="text/javascript">alert("'.$msg.'");window.history.go(-1);</script>';exit;
        } else {
            echo '<script type="text/javascript">alert("'.$msg.'");window.history.go(-1);</script>';
        }
    }
    
    public static function buildTree(array $elements, $parentId = 0)
    {
        $branch = array();
        
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = self::buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                
                $branch[] = $element;
            }
        }
        
        return $branch;
    }
}