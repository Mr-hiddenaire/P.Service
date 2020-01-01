<?php
namespace App\Services\OriginalSource;

use App\Services\OriginalSource\BaseService;

use App\Model\OriginalSource\ContentsModel;

class ContentsService extends BaseService
{
    protected $contentsModel;
    
    public function __construct(ContentsModel $conentsModel)
    {
        $this->contentsModel = $conentsModel;
    }
    
    public function getInfo($where, $fields, array $orderBy)
    {
        return $this->contentsModel->getInfo($where, $fields, $orderBy);
    }
}