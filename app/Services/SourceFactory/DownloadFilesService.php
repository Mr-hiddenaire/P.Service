<?php
namespace App\Services\SourceFactory;

use App\Services\SourceFactory\BaseService;

use App\Model\SourceFactory\DownloadFilesModel;

class DownloadFilesService extends BaseService
{
    protected $downloadFilesModel;
    
    public function __construct(DownloadFilesModel $downloadFilesModel)
    {
        $this->downloadFilesModel = $downloadFilesModel;
    }
    
    public function getInfo($where, $fields, array $orderBy)
    {
        return $this->downloadFilesModel->getInfo($where, $fields, $orderBy);
    }
    
    public function addInfo(array $data)
    {
        return $this->downloadFilesModel->addInfo($data);
    }
    
    public function updateInfo($where, array $data)
    {
        return $this->downloadFilesModel->updateInfo($where, $data);
    }
    
    public function deleteInfo($where)
    {
        return $this->downloadFilesModel->deleteInfo($where);
    }
}