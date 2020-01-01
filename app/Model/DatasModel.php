<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DatasModel extends Model
{
    public $timestamps = false;
    
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
    
        $this->table = env('DB_PREFIX').'datas';
    }
    
    public function getDatas($where, $orWhere, $page, $pageSize, array $orderBy, ...$fields)
    {
        $obj = $this->select($fields)->where($where)->orWhere($orWhere)->forPage($page, $pageSize)->orderBy($orderBy[0], $orderBy[1])->get();
    
        if ($obj) {
            return $obj->toArray();
        } else {
            return [];
        }
    }
    
    public function getDatas2($where, $page, $pageSize, array $orderBy, ...$fields)
    {
        $obj = $this->select($fields)->where($where)->forPage($page, $pageSize)->groupBy('file_hash')->orderBy($orderBy[0], $orderBy[1])->get();
    
        if ($obj) {
            return $obj->toArray();
        } else {
            return [];
        }
    }
    
    public function getTotal($where)
    {
        return $this->where($where)->count();
    }
    
    public function getDatasByWhereIn($where, $page, $pageSize, array $orderBy, ...$fields)
    {
        $obj = $this->select($fields)->whereIn('file_hash', $where)->forPage($page, $pageSize)->orderBy($orderBy[0], $orderBy[1])->get();
    
        if ($obj) {
            return $obj->toArray();
        } else {
            return [];
        }
    }
}
