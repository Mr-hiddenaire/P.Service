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

function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object)) {
                    rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                } else {
                    echo 'deleting file'.$dir.DIRECTORY_SEPARATOR .$object;
                    unlink($dir. DIRECTORY_SEPARATOR .$object);
                }
            }
        }
    }
}

function doSpecialFilenameReformat(string $filename)
{
    $specialCharacters = [' ', '\\', '|', '\[', '\]', ];
    
    foreach ($specialCharacters as $specialCharacter) {
        $filename = str_replace($specialCharacter, '\\'.$specialCharacter, $filename);
    }
    
    return stripslashes($filename);
}

function base64EncodeImage($imageFile)
{
    $base64Image = '';
    
    $imageInfo = getimagesize($imageFile);
    $imageData = fread(fopen($imageFile, 'r'), filesize($imageFile));
    
    $base64Image = 'data:'.$imageInfo['mime'].';base64,'.base64_encode($imageData);
    
    return $base64Image;
}