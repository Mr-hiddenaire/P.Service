<?php
namespace App\Services\SourceFactory;

use App\Services\SourceFactory\BaseService;

use App\Model\SourceFactory\DownloadFileRecordsModel;

class DownloadFileRecordsService extends BaseService
{
    protected $downloadFileRecordsModel;
    
    public function __construct(DownloadFileRecordsModel $downloadFileRecordsModel)
    {
        $this->downloadFileRecordsModel = $downloadFileRecordsModel;
    }
    
    public function getInfo($where, $fields, array $orderBy)
    {
        return $this->downloadFileRecordsModel->getInfo($where, $fields, $orderBy);
    }
    
    public function addInfo(array $data)
    {
        return $this->downloadFileRecordsModel->addInfo($data);
    }
    
    public function updateInfo($where, array $data)
    {
        return $this->downloadFileRecordsModel->updateInfo($where, $data);
    }
    
    public function deleteInfo($where)
    {
        return $this->downloadFileRecordsModel->deleteInfo($where);
    }
}