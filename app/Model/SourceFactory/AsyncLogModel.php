<?php

namespace App\Model\SourceFactory;

use App\Model\SourceFactory\BaseModel;

class AsyncLogModel extends BaseModel
{
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        
        $this->table = 'async_log';
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
    
    public function addAsyncLog(array $data)
    {
        return self::insert($data);
    }
    
    public function updateAsyncLog($where, array $data)
    {
        return self::where($where)->update($data);
    }
}
