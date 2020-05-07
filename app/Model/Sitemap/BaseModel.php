<?php

namespace App\Model\Sitemap;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $connection= 'source_factory';
    
    public $timestamps = false;
    
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
    }
}
