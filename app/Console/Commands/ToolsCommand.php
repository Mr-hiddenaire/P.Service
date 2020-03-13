<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\OriginalSource\ContentsService;

class ToolsCommand extends Command
{
    protected $contentsService;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tool:cmd {--method=%s} {--original-id=%d}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tools';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ContentsService $contentsService
        )
    {
        $this->contentsService = $contentsService;
        
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->{$this->option('method')}();
    }
    
    private function getJsonContent()
    {
        $originalId = intval($this->option('original-id'));
        
        $res = $this->contentsService->getInfo([
            ['id', '=', $originalId]
        ], ['*'], ['id', 'DESC']);
        
        echo json_encode($res);
    }
}
