<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    public $timestamps = false;
    
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
    
        $this->table = env('DB_PREFIX').'web_user';
    }
}
