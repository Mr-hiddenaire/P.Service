<?php

namespace App\Model\SourceFactory;

use App\Model\SourceFactory\BaseModel;

class DownloadFilesModel extends BaseModel
{
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        
        $this->table = 'downloaded_files';
    }
    
    public function getInfo($where, $fields, array $orderBy)
    {
        $res = self::where($where)->orderBy($orderBy[0], $orderBy[1])->first($fields);
        
        if ($res) {
            return $res->toArray();
        } else {
            return [];
        }
    }
    
    public function addInfo(array $data)
    {
        return self::insert($data);
    }
    
    public function updateInfo($where, array $data)
    {
        return self::where($where)->update($data);
    }
    
    public function deleteInfo($where)
    {
        return self::where($where)->delete();
    }
}
