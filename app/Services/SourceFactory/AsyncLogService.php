<?php
namespace App\Services\SourceFactory;

use App\Services\SourceFactory\BaseService;

use App\Model\SourceFactory\AsyncLogModel;

class AsyncLogService extends BaseService
{
    protected $asyncLogModel;
    
    public function __construct(AsyncLogModel $asyncModel)
    {
        $this->asyncLogModel = $asyncModel;
    }
    
    public function getInfo($where, $fields, array $orderBy)
    {
        return $this->asyncLogModel->getInfo($where, $fields, $orderBy);
    }
    
    public function addAsyncLog(array $data)
    {
        return $this->asyncLogModel->addAsyncLog($data);
    }
    
    public function updateAsyncLog($where, array $data)
    {
        return $this->asyncLogModel->updateAsyncLog($where, $data);
    }
}