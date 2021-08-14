<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Spatie\Sitemap\SitemapGenerator;
use Psr\Http\Message\UriInterface;

class SitemapCommand extends Command
{
    const SHOULD_CRAWL_LIST = [
        '/viewforum.php',
        '/viewtopic.php',
        '/search.php.php',
        '/',
    ];
    
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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        SitemapGenerator::create(config('app.url'))->shouldCrawl(function (UriInterface $url) {
            if (in_array($url->getPath(), self::SHOULD_CRAWL_LIST)) {
                return true;
            }
            
            return false;
            
        })->setMaximumCrawlCount(1000)->writeToFile(config('sitemap.P_SITE_ROOT_PATH'));
    }
}
