<?php


class File_Convert_Solution_ffmpeg extends File_Convert_Solution
{
    
   
    static $rules = array(
        array(
         
            'from' =>    array( //source
                'video/avi',
                'video/x-ms-wmv',
                'video/mp4',
                'video/x-msvideo',
                'video/mpeg',
                'video/quicktime',
            ),
            'to' =>    array( //target
                'image/jpeg',
            )
        ),
        
         
    );
    
    function convert($fn,$x,$y,$pg)
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
    
}