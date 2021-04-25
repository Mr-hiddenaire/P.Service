<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Aws\CloudFront\CookieSigner;

class AwsSignerCommand extends Command
{
    protected $cookieSigner;
    
    protected $keyPairId;
    
    protected $privateKey;
    
    protected $policies;
    
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
        $this->keyPairId = config('aws.cloudfront.sign.key_pair_id');
        $this->privateKey = config('aws.cloudfront.sign.private_key_file_path');
        $this->policies = json_encode(config('aws_policies.policies'), JSON_UNESCAPED_SLASHES);
        
        $this->cookieSigner = new CookieSigner($this->keyPairId, $this->privateKey);
        
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
        $sign = $this->cookieSigner->getSignedCookie(null, null, $this->policies);
        
        echo json_encode($sign, JSON_UNESCAPED_SLASHES);
    }
    
    protected function doUrlSign()
    {
        
    }
}
