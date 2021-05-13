<?php


    function ffmpeg($fn)
    {
        
         
        $ext = 'jpg';
        $target = $fn . '.' . $ext;
        if (file_exists($target)  && filesize($target) && filemtime($target) > filemtime($fn)) {
            return $target;
        }
        require_once 'System.php';
        $ffmpeg = System::which('ffmpeg');
        if (!$ffmpeg) {
            throw new Exception("ffmpeg missing, can not convert file");
        }
        $cmd = "$ffmpeg   -i " .
                escapeshellarg($fn) ." -vcodec mjpeg -vframes 1 -an -f rawvideo -ss 20  -s 320x240 " . escapeshellarg($target);

        ///echo $cmd;
        $this->exec($cmd);
        
        clearstatcache();
        
        return  file_exists($target)  && filesize($target) ? $target : false;
        
        
        
    }