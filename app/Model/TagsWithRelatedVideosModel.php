<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TagsWithRelatedVideosModel extends Model
{
    public $timestamps = false;
    
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
    
        $this->table = env('DB_PREFIX').'tags_with_videos';
    }
    
    public function getDatas($where, $page, $pageSize, array $orderBy, ...$fields)
    {
        $offset = ($page - 1)*$pageSize;
    
        $obj = $this->select($fields)->where($where)->offset($offset)->limit($pageSize)->orderBy($orderBy[0], $orderBy[1])->get();
    
        if ($obj) {
            return $obj->toArray();
        } else {
            return [];
        }
    }
}
