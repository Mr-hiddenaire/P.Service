<?php

namespace App\Logging;

use Illuminate\Http\Request;
use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;

class JsonFormatter extends BaseJsonFormatter
{
    public function format(array $record)
    {
        dd($record);
        $dur = number_format(microtime(true) - LARAVEL_START, 3);

        $context = [];

        if (!empty($record['context'])) {
            $context = $record['context'];
        }

        $serverAddr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
        $serverServerName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
        $token = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        $url = app(Request::class)->url();
        $httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        $newRecord = [
            'request_id' => REQUEST_ID,
            'project_name' => APP_NAME,
            'level' => $record['level_name'],
            'request_time' => $record['datetime']->format('Y-m-d H:i:s.u'),
            'message' => $record['message'],
            'token' => $token,
            'context' => json_encode($context,JSON_UNESCAPED_UNICODE),
            'server_addr' => $serverAddr,
            'duration' => $dur,
            'url' => $url,
            'referer' => $httpReferer,
            'cip' => app(Request::class)->ip(),
            'method' => app(Request::class)->method(),
            'error_code' => $record['level'] ?? 0,
            'error_label' => $record['level_name'] ?? '',
            'upstream_addr' => $serverAddr,
            'upstream_domain' => $serverServerName,
            'line' => '',
            'file_name' => '',
            'function_name' => '',
            'class' => '',
        ];

        if (isset($record['extra'])) {
            $newRecord['line']          = $record['extra']['line'] ?? '';
            $newRecord['file_name']     = $record['extra']['file'] ?? '';
            $newRecord['function_name'] = $record['extra']['function'] ?? '';
            $newRecord['class']         = $record['extra']['class'] ?? '';
        }

        return $this->toJson($this->normalize($newRecord), true).($this->appendNewline ? "\n" : '');
    }
}
