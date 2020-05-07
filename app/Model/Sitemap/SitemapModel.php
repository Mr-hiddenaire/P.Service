<?php

namespace App\Model\Sitemap;

use App\Model\Sitemap\BaseModel;

class SitemapModel extends BaseModel
{
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        
        $this->table = 'sitemap';
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
    
    public function addContents(array $data)
    {
        return self::insert($data);
    }
    
    public function deleteInfo($where)
    {
        return self::where($where)->delete();
    }
    
    public function getData($pageSize, $offset, $where, $fields)
    {
        $res = self::where($where)->limit($pageSize)->offset($offset)->orderBy('id', 'DESC')->get($fields);
        
        if ($res) {
            return $res->toArray();
        } else {
            return [];
        }
    }
    
    public function modify($where, $data)
    {
        return self::where($where)->update($data);
    }
}
