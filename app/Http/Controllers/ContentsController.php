<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SourceFactory\ContentsService;

use App\Constants\Common;

class ContentsController extends Controller
{
    protected $contentsService;
    
    public function __construct(ContentsService $contentsService)
    {
        $this->contentsService = $contentsService;
    }
    
    public function getShoudSyncData(Request $request)
    {
        $page = $request->input('page') ?? 1;
        $pageSize = $request->input('page_size') ?? Common::MAX_PAGE_SIZE;
        $type = $request->input('type') ?? Common::IS_AISA;
        
        $page = intval($page);
        $pageSize = intval($pageSize);
        $type = intval($type);
        
        $offset = ($page - 1)*$pageSize;
        
        if ($pageSize > Common::MAX_PAGE_SIZE) {
            $pageSize = Common::MAX_PAGE_SIZE;
        }
        
        $where = [
            ['is_sync_status', '=', Common::IS_NOT_SYNC],
            ['type', '=', $type],
        ];
        
        $fields = [
            '*'
        ];
        
        $res = $this->contentsService->getData($pageSize, $offset, $where, $fields);
        
        return response()->json([
            'retcode' => 200,
            'retmsg' => 'ok',
            'data' => $res
        ]);
    }
    
    public function setSyncStatus(Request $request)
    {
        $id = intval($request->input('id')) ?? 0;
        
        if (!$id) {
            return response()->json([
                'retcode' => 200,
                'retmsg' => 'id error',
                'data' => []
            ]);
        }
        
        $res = $this->contentsService->modify([['id', '=', $id]], ['is_sync_status' => Common::IS_SYNC]);
        
        return response()->json([
            'retcode' => 200,
            'retmsg' => 'ok',
            'data' => $res
        ]);
    }
}
