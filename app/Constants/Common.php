<?php

namespace App\Constants;

class Common
{
    // Asia type
    const IS_AISA = 1;
    
    // Euro type
    const IS_EURO = 2;
    
    // Picked up from original source
    const IS_PICKED_UP = 1;
    
    // Not picked up from original source yet
    const IS_NOT_PICKED_UP = 0;
    
    // Downloaded by transmission finished
    const IS_DOWNLOAD_FINISHED = 1;
    
    // Not downloaded by transmission yet
    const IS_DOWNLOAD_NOT_FINISHED_YET = 0;
    
    // Is synced to website
    const IS_SYNC = 2;
    
    // Is not synced to website
    const IS_NOT_SYNC = 1;
    
    // The max page size
    const MAX_PAGE_SIZE = 100;
    
    const IS_UPOADING = 1;
    
    // Hls making
    const HLS_IS_MAKING = 1;
    
    // Hls cutting done
    const HLS_DONE_CUTTING = 2;
    
    // Hls upload done
    const HLS_DONE_UPLOAD = 3;
}