<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use Illuminate\Support\Facades\Route;

Route::post('/getTopTags', 'ServiceController@getTopTags');
Route::post('/getRecentVideos', 'ServiceController@getRecentVideos');
Route::post('/getRecentVideosTotal', 'ServiceController@getRecentVideosTotal');
Route::post('/getVideoInfoById', 'ServiceController@getVideoInfoById');
Route::post('/getRelatedVideos', 'ServiceController@getRelatedVideos');

Route::post('/register', 'ServiceController@register');
Route::post('/getUserInfoByEmail', 'ServiceController@getUserInfoByEmail');

Route::post('/getShoudSyncData', 'ContentsController@getShoudSyncData');