<?php

namespace App\Model\OriginalSource;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $connection= 'original_source';
    
    public $timestamps = false;
    
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
    }
}
