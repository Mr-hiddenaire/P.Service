<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\DatasModel;
use App\Model\TagsModel;
use App\Model\UserModel;
use App\Model\RelatedVideosModel;

class ServiceController extends Controller
{
    const VIDEO_DONE = 1;
    const IS_RELATED_VIDEO = 1;
    
    public function getRecentVideos(Request $request)
    {
        $datasModel = new DatasModel();
        
        $where = [];
        $orWhere = [];
        
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 18);
        $selfId = $request->input('selfId', 0);
        $s = $request->input('s');
        
        $tag = $request->input('tag');
        
        $where[] = ['status', '=', self::VIDEO_DONE];
        
        // Pending to uncomment it.
        $where[] = ['is_related', '=', self::IS_RELATED_VIDEO];
        
        if ($tag) {
            $where[] = ['tags', 'like', '%'.$tag.'%'];
        }
        
        if ($selfId > 0) {
            $where[] = ['id', '!=', $selfId];
        }
        
        if ($s) {
            $where[] = ['title', 'like', '%'.$s.'%'];
            $orWhere[] = ['tags', 'like', '%'.$s.'%'];
        }
        
        $datas = $datasModel->getDatas($where, $orWhere, $page, $pageSize, ['addtime', 'DESC'], '*');
        
        return response()->json([
            'retcode' => 200,
            'retmsg' => 'SUCCESS',
            'data' => $datas
        ]);
    }
    
    public function getRecentVideosTotal(Request $request)
    {
        $datasModel = new DatasModel();
        
        $where = [];
        $page = $request->input('page', 1);
        $tag = $request->input('tag');
        $s = $request->input('s');
        
        $where[] = ['status', '=', self::VIDEO_DONE];
        $where[] = ['is_related', '=', self::IS_RELATED_VIDEO];
        
        if ($tag) {
            $where[] = ['tags', 'like', '%'.$tag.'%'];
        }
        
        if ($s) {
            $where[] = ['title', 'like', '%'.$s.'%'];
        }
        
        $datas = $datasModel->getTotal($where);
        
        return response()->json([
            'retcode' => 200,
            'retmsg' => 'SUCCESS',
            'data' => $datas
        ]);
    }
    
    public function getVideoInfoById(Request $request)
    {
        $datasModel = new DatasModel();
        
        $id = $request->input('id');
        
        $info = $datasModel::where([['file_hash', '=', $id]])->first();
        
        return response()->json([
            'retcode' => 200,
            'retmsg' => 'SUCCESS',
            'data' => $info
        ]);
    }
    
    public function getRelatedVideos(Request $request)
    {
        $relatedVideosModel = new RelatedVideosModel();
        $datasModel = new DatasModel();
    
        $relatedVideoFileHash = [];
        
        $originVideoId = $request->input('origin_video_id');
    
        $info = $relatedVideosModel->getDatas([['origin_video_id', '=', $originVideoId]], 1, 100, ['id', 'DESC'], '*');
    
        foreach ($info as $relatedVideoInfo) {
            $relatedVideoFileHash[] = $relatedVideoInfo['related_file_hash'];
        }
        
        $relatedVideos = $datasModel->getDatasByWhereIn($relatedVideoFileHash, 1, 100, ['id', 'DESC'], '*');
        
        return response()->json([
            'retcode' => 200,
            'retmsg' => 'SUCCESS',
            'data' => $relatedVideos
        ]);
    }
    
    public function getTopTags(Request $request)
    {
        $tagsModel = new TagsModel();
        
        $topTags = $tagsModel->getDatas([], 1, 9999, ['hot_level', 'DESC'], 'name');
    
        return response()->json([
            'retcode' => 200,
            'retmsg' => 'SUCCESS',
            'data' => $topTags
        ]);
    }
    
    public function register(Request $request)
    {
        $userModel = new UserModel();
        
        $data = $request->input('data');
        
        $data = json_decode($data, true);
        
        $res = $userModel::insert($data);
        
        if ($res) {
            return response()->json([
                'retcode' => 200,
                'retmsg' => 'SUCCESS',
                'data' => []
            ]);
        } else {
            return response()->json([
                'retcode' => -999,
                'retmsg' => 'FAILURE',
                'data' => []
            ]);
        }
    }
    
    public function getUserInfoByEmail(Request $request)
    {
        $userModel = new UserModel();
        
        $email = $request->input('email');
        
        $info = $userModel::where([['email', '=', $email]])->first();
        
        return response()->json([
            'retcode' => 200,
            'retmsg' => 'SUCCESS',
            'data' => $info
        ]);
    }
}
