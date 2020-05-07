<?php
namespace App\Services\Sitemap;

use App\Services\Sitemap\SitemapService;

use App\Model\Sitemap\SitemapModel;

class SitemapService extends BaseService
{
    protected $sitemapModel;
    
    public function __construct(SitemapModel $sitemapModel)
    {
        $this->sitemapModel = $sitemapModel;
    }
    
    public function getInfo($where, $fields, array $orderBy)
    {
        return $this->sitemapModel->getInfo($where, $fields, $orderBy);
    }
    
    public function deleteInfo($where)
    {
        return $this->sitemapModel->deleteInfo($where);
    }
    
    public function addContents(array $data)
    {
        return $this->sitemapModel->addContents($data);
    }
    
    public function getData($pageSize, $offset, $where, $fields)
    {
        return $this->sitemapModel->getData($pageSize, $offset, $where, $fields);
    }
    
    public function modify($where, $data)
    {
        return $this->sitemapModel->modify($where, $data);
    }
}