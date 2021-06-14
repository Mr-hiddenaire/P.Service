<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Aws\S3\S3Client;

class AwsFileSyncCommand extends Command
{
    protected $s3Client;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aws:file:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AWS File Sync';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->s3Client = new S3Client([
            'profile' => 'default',
            'region' => 'us-east-1',
            'version' => 'latest',
        ]);
        
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $res = $this->s3Client->uploadDirectory('/Users/Jim/Downloads/s3', 'dailyporns', 'hls-bundle/test');
        
        var_dump($res);
    }
}
