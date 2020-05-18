<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Spatie\Sitemap\SitemapGenerator;
use Psr\Http\Message\UriInterface;

use App\Services\Sitemap\SitemapService;

class SitemapCommand extends Command
{
    const CHUNK_SIZE = 100;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap';
    
    protected $sitemapService;

    /**
     * Create a new command instance.
     *
     * @param SitemapService $sitemapService
     */
    public function __construct(SitemapService $sitemapService)
    {
        parent::__construct();
        
        $this->sitemapService = $sitemapService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        SitemapGenerator::create(config('app.url'))->shouldCrawl(function () {
            return true;
            
        })->setMaximumCrawlCount(self::CHUNK_SIZE)->writeToFile(config('sitemap.P_SITE_ROOT_PATH'));
        
        $sitemapXMLStr = file_get_contents(config('sitemap.P_SITE_ROOT_PATH'));
        
        $sitemapXML = simplexml_load_string($sitemapXMLStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        
        $sitemapJson = json_encode($sitemapXML);
        $sitemapArr = json_decode($sitemapJson, true);
        
        dd($sitemapArr);
    }
}
