<?php

namespace App\Common;

use Illuminate\Support\Facades\Log;

class Transmission
{
    private $_transmissionHost;
    
    private $_transmissionPort;
    
    private $_transmissionLocation;
    
    private $_transmissionRPC;
    
    private $_transmissionSessionId;
    
    public function __construct()
    {
        if (env('APP_ENV') == 'production') {
            $this->_transmissionHost = env('TRANSMISSION_HOST');
            
            $this->_transmissionPort = env('TRANSMISSION_PORT');
            
            $this->_transmissionLocation = env('TRANSMISSION_LOCATION');
            
            $this->_transmissionRPC = 'http://'.$this->_transmissionHost.':'.$this->_transmissionPort.'/'.$this->_transmissionLocation;
            
            $this->_transmissionSessionId = $this->getTransmissionSessionId();
        }
    }
    
    private function getTransmissionSessionId()
    {
        $headers = [];
        
        $fp = @fsockopen($this->_transmissionHost, $this->_transmissionPort, $errno, $errstr, 30);
        
        if (!$fp) {
            Log::info("Can not connect to transmission: $errstr ($errno)");
        }
        
        $out = "GET /".$this->_transmissionLocation." HTTP/1.1\r\n";
        $out .= "Host: ".$this->_transmissionHost."\r\n";
        $out .= "Connection: Close\r\n\r\n";
        
        fwrite($fp, $out);
        
        $info = stream_get_contents($fp);
        
        fclose($fp);
        
        $info = explode("\r\n", $info);
        
        foreach ($info as $i) {
            $i = explode(": ", $i);
            
            if (isset($i[0]) && isset($i[1])) {
                $headers[$i[0]] = $i[1];
            }
        }
        
        return $headers['X-Transmission-Session-Id'];
    }
    
    private function doPostRequest($url, $data)
    {
        $params = [];
        
        $params['http'] = [];
        $params['http']['method'] = 'POST';
        $params['http']['content'] = $data;
        $params['http']['header'] = "X-Transmission-Session-Id: ".$this->_transmissionSessionId."\r\n";
        
        $ctx = stream_context_create($params);
        
        $fp = @fopen($url, 'rb', false, $ctx);
        
        if (!$fp) {
            Log::info("Problem with $url");
        }
        
        $response = @stream_get_contents($fp);
        
        if ($response === false) {
            Log::info("Problem reading data from $url");
        }
        
        return $response;
    }
    
    public function doRemove()
    {
        $request = [];
        
        $request['method'] = 'torrent-get';
        $request['arguments'] = [];
        $request['arguments']['fields'] = ['id', 'name', 'doneDate', 'haveValid', 'totalSize'];
        
        try {
            $reply = json_decode($this->doPostRequest($this->_transmissionRPC, json_encode($request)));
        } catch (\Exception $e) {
            Log::info("*** Exception: %s\n", $e->getMessage());
        }
        
        $arr = $reply->arguments->torrents;
        
        foreach ($arr as $tor)
        {
            if ($tor->haveValid == $tor->totalSize) {
                Log::info(sprintf("Torrent '%s' finished on %s\n", $tor->name, strftime("%Y-%b-%d %H:%M:%S", $tor->doneDate)));
                
                $request = array('method' => 'torrent-remove', 'arguments' => ['ids' => [$tor->id]]);
                
                try {
                    $reply = json_decode($this->doPostRequest($this->_transmissionRPC, json_encode($request)));
                } catch (\Exception $e) {
                    Log::info("*** Exception: %s\n", $e->getMessage());
                }
                
                if ($reply->result != 'success') {
                    Log::info("*** Failed to remove torrent ***\n");
                }
            }
        }
    }
    
    public function doRemoveForce()
    {
        $request = [];
        
        $request['method'] = 'torrent-get';
        $request['arguments'] = [];
        $request['arguments']['fields'] = ['id', 'name', 'doneDate', 'haveValid', 'totalSize'];
        
        try {
            $reply = json_decode($this->doPostRequest($this->_transmissionRPC, json_encode($request)));
        } catch (\Exception $e) {
            Log::info("*** Exception: %s\n", $e->getMessage());
        }
        
        $arr = $reply->arguments->torrents;
        
        foreach ($arr as $tor)
        {
            Log::info(sprintf("Torrent '%s' finished on %s\n", $tor->name, strftime("%Y-%b-%d %H:%M:%S", $tor->doneDate)));
            
            $request = array('method' => 'torrent-remove', 'arguments' => ['ids' => [$tor->id]]);
            
            try {
                $reply = json_decode($this->doPostRequest($this->_transmissionRPC, json_encode($request)));
            } catch (\Exception $e) {
                Log::info("*** Exception: %s\n", $e->getMessage());
            }
            
            if ($reply->result != 'success') {
                Log::info("*** Failed to remove torrent ***\n");
            }
        }
    }
}