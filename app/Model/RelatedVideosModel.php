<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RelatedVideosModel extends Model
{
    public $timestamps = false;
    
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
    
        $this->table = env('DB_PREFIX').'related_videos';
    }
    
    public function getDatas($where, $page, $pageSize, array $orderBy, ...$fields)
    {
        $obj = $this->select($fields)->where($where)->forPage($page, $pageSize)->orderBy($orderBy[0], $orderBy[1])->get();
    
        if ($obj) {
            return $obj->toArray();
        } else {
            return [];
        }
    }
}
