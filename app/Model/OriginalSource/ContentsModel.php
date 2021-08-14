<?php

namespace App\Model\OriginalSource;

use App\Model\OriginalSource\BaseModel;

class ContentsModel extends BaseModel
{
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        
        $this->table = 'contents';
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
    
    public function modify($where, array $data)
    {
        return self::where($where)->update($data);
    }
}
