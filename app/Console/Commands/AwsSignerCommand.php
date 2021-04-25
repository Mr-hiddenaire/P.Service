<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Aws\CloudFront\CookieSigner;

class AwsSignerCommand extends Command
{
    protected $cookieSigner;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:sign {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AWS Signer';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $keyPairId = config('aws.cloudfront.sign.key_pair_id');
        $privateKey = config('aws.cloudfront.sign.private_key_file_path');
        
        $this->cloudFrontClient = new CookieSigner($keyPairId, $privateKey);
        
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = $this->option('type') ?? 'cookie';
        
       switch ($type) {
           case 'cookie':
               return $this->doCookieSign();
           case 'url':
               return $this->doUrlSign();
           default:
               return $this->doCookieSign();
       }
    }
    
    protected function doCookieSign()
    {
        echo 'do cookie sign';
    }
    
    protected function doUrlSign()
    {
        
    }
}
