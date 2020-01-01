<?php

function isMobile($mobile)
{
    return pregValidation('/^1(3|4|5|6|7|8|9)[0-9]{9}$/', $mobile);;
}

function isRealName($username)
{
    return pregValidation('/^[\x{4e00}-\x{9fa5}]{1,10}/u', $username);
}

function isIdCard($idCard)
{
    return pregValidation('/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{2}(\d|X)?$/i', $idCard);
}

function isIdCard2($idCard)
{
    return pregValidation('/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}(\d|X)?$/i', $idCard);
}

function isEmail($mobile)
{
    return pregValidation('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $mobile);
}

function isUrl($url)
{
    return pregValidation('/^[http:\/\/:][a-zA-Z0-9.&\/?]+/', $url);
}

function isSimplePassword($password)
{
    return pregValidation('/[a-zA-Z0-9]/', $password);
}

function pregValidation($pregPattern, $validateSubject, $storeResult = array())
{
    preg_match($pregPattern, $validateSubject, $storeResult);

    if (!empty($storeResult))
    {
        return true;
    }
    else
    {
        return false;
    }
}

function getMillisecond()
{
    list($t1, $t2) = explode(' ', microtime());

    return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
}

function numberShowFriendly($number)
{
    if ($number == 0)
        return '0.00';

    $cell = explode('.', $number);
    
    if (isset($cell[0]))
    {
        $prefix = $cell[0];
    }
    else
    {
        $prefix = 0;
    }
    
    if (isset($cell[1]))
    {
        $suffix = substr($cell[1], 0, 2);
    }
    else
    {
        $suffix = '00';
    }
    
    $result = $prefix.'.'.$suffix;
    
    return $result;
}

function detectMIME($filename)
{
    $file = fopen($filename, 'rb');
    $finfo = finfo_open(FILEINFO_MIME);

    // 直接读取文件的前4个字节，根据硬编码判断
    $file = fopen($filename, 'rb');

    //只读文件头4字节
    $bin = fread($file, 4);

    fclose($file);

    $strInfo = @unpack('C4chars', $bin);

    //dechex() 函数把十进制转换为十六进制。
    $typeCode = dechex($strInfo['chars1']).dechex($strInfo['chars2']).dechex($strInfo['chars3']).dechex($strInfo['chars4']);

    //硬编码值查表
    switch ($typeCode)
    {
        case '25504446':
            $type = 'pdf';
            break;
        case 'd0cf11e0':
        case '504b34':
            $type = 'office';
            break;
        default:
            $type = 'error';
            break;
    }

    return $type;
}

function stylefiltration($str)
{
    $str=preg_replace("/style=('|\")[^\"]*?('|\")/","",$str);
    
    return $str;
}

function contentHtmlEntityDecode($content)
{
    return strip_tags(html_entity_decode($content));
}

function randomNumber($length)
{
    $randstr = '';
    $str = '0123456789';

    $len = strlen($str) - 1;

    for($i = 0; $i < $length; $i++) {
        $num = mt_rand(0, $len);
        $randstr .= $str[$num];
    }

    return $randstr;
}