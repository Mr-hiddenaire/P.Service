<?php
namespace App\Services\SourceFactory;

use App\Services\SourceFactory\BaseService;

use App\Model\SourceFactory\ContentsModel;

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
    
    public function deleteInfo($where)
    {
        return $this->contentsModel->deleteInfo($where);
    }
    
    public function addContents(array $data)
    {
        return $this->contentsModel->addContents($data);
    }
    
    public function getData($pageSize, $offset, $where, $fields)
    {
        return $this->contentsModel->getData($pageSize, $offset, $where, $fields);
    }
    
    public function modify($where, $data)
    {
        return $this->contentsModel->modify($where, $data);
    }
}