<?php

namespace App\Tools;

use GuzzleHttp\Client;

class ImagesUploader
{
    private $_client;
    
    public function __construct(Client $client)
    {
        $this->_client = $client;
    }

    public function Ibb($imageUrl)
    {
        $data = [];
        
        $configuration = config('images.ibb');
        
        if (file_exists($imageUrl)) {
            $image = base64_encode(file_get_contents($imageUrl));
        } else {
            $image = $imageUrl;
        }
        
        $postData = [
            'key' => $configuration['key'],
            'image' => $image,
            'name' => time(),
        ];
        
        $response = $this->_client->post($configuration['endpoint'], [
            'form_params' => $postData,
        ]);
        
        $res = json_decode($response->getBody(), true);
        
        if (isset($res['data']['image']['url']) && $res['data']['image']['url']) {
            $originalUrl = $res['data']['image']['url'];
        } else {
            $originalUrl= '';
        }
        
        if (isset($res['data']['thumb']['url']) && $res['data']['thumb']['url']) {
            $thumbUrl = $res['data']['thumb']['url'];
        } else {
            $thumbUrl = '';
        }
        
        $data = [
            'original_url' => $originalUrl,
            'thumb_url' => $thumbUrl,
        ];
        
        return $data;
    }
}