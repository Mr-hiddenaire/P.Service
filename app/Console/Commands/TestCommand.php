<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Common\Transmission;
use App\Tools\ImagesUploader;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test command';
    
    protected $transmission;
    
    protected $imagesUploader;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Transmission $transmission, ImagesUploader $imagesUploader)
    {
        parent::__construct();
        
        $this->transmission = $transmission;
        
        $this->imagesUploader = $imagesUploader;
    }
    
    public function handle()
    {
        $this->testIbbUpload();
    }
    
    public function testTransmissionDoRemove()
    {
        $this->transmission->doRemove();
    }
    
    public function testIbbUpload()
    {
        $res = $this->imagesUploader->Ibb('https://img-l3.xvideos-cdn.com/videos/thumbs169poster/8e/26/0c/8e260c62f644f0e9e5035b8a4afd638e/8e260c62f644f0e9e5035b8a4afd638e.1.jpg');
        
        dd($res);
    }
}
